<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use App\Domain\Contracts\Repositories\PendingServiceRepository;
use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\Plan;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesSubscriptionsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerPreferencesTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerTranslator;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription as PestRoutesSubscription;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PestRoutesPendingServiceRepository implements PendingServiceRepository
{
    private const ACTIVE_SUBSCRIPTION_FLAG = 1;
    private const BATCH_SIZE = 200;
    private const INITIAL_APPOINTMENT = 'initial';
    private const CANCELLATION_PERIOD_DAYS = 7;
    private const SCHEDULING_PERIOD_DAYS = 14;

    /** @var Collection<PestRoutesServiceType>  */
    private Collection $serviceTypes;
    private Office $office;
    private Plan $plan;
    private CarbonInterface $date;

    public function __construct(
        private readonly PestRoutesSubscriptionsDataProcessorCacheWrapper $subscriptionsDataProcessor,
        private readonly PestRoutesAppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly PestRoutesCustomersDataProcessor $customersDataProcessor,
        private readonly ServiceTypesDataProcessor $serviceTypesDataProcessor,
        private readonly PestRoutesCustomerTranslator $customerTranslator,
        private readonly PestRoutesCustomerPreferencesTranslator $customerPreferencesTranslator,
        private readonly PestRoutesSchedulingAppointmentTranslator $appointmentTranslator,
    ) {
    }

    /**
     * @param Office $office
     * @param CarbonInterface $date
     * @param Plan $plan
     *
     * @return Collection<PendingService>
     */
    public function findByOfficeIdAndDate(Office $office, CarbonInterface $date, Plan $plan): Collection
    {
        $this->office = $office;
        $this->plan = $plan;
        $this->date = $date;

        $this->resolveAppointmentServiceTypes();

        $startDate = Carbon::tomorrow($this->office->getTimezone())->subYear();
        $endDate = Carbon::tomorrow($this->office->getTimezone())->addDays(self::SCHEDULING_PERIOD_DAYS);

        $subscriptionsIds = $this->subscriptionsDataProcessor->extractIds(
            $this->office->getId(),
            new SearchSubscriptionsParams(
                officeIds: [$this->office->getId()],
                active: self::ACTIVE_SUBSCRIPTION_FLAG,
                serviceId: $this->plan->getServiceTypeId(),
                lastCompleted: DateFilter::between($startDate, $endDate),
            )
        );

        if (empty($subscriptionsIds)) {
            return collect();
        }

        $pendingServices = new Collection();

        for ($i = 0; $i < count($subscriptionsIds); $i += self::BATCH_SIZE) {
            $batchSubscriptionsIds = array_slice($subscriptionsIds, $i, self::BATCH_SIZE);

            $pendingServices = $pendingServices->merge(
                $this->getPendingServices($batchSubscriptionsIds)
            );
        }

        // TODO: Consider a case when customer has renewal subscription

        // TODO: If there was a reservice appointment recently, should we follow subscription schedule?

        return $pendingServices;
    }

    private function getPlanDueDate(): CarbonInterface
    {
        $intervalDays = $this->plan->getServiceIntervalDays($this->date);

        return $this->date->clone()->subDays($intervalDays);
    }

    private function resolveAppointmentServiceTypes(): void
    {
        $this->serviceTypes = $this->serviceTypesDataProcessor->extract(
            $this->office->getId(),
            new SearchServiceTypesParams(
                officeIds: [$this->office->getId()]
            )
        )->keyBy('id');
    }

    /**
     * @param int[] $subscriptionIds
     *
     * @return Collection<PendingService>
     */
    private function getPendingServices(array $subscriptionIds): Collection
    {
        $officeId = $this->office->getId();
        $initialFollowUpDueDate = $this->date->clone()->subDays($this->plan->getInitialFollowUpDays());

        $activeSubscriptions = $this->getActiveSubscriptions($subscriptionIds);
        $subscriptionIds = $activeSubscriptions->pluck('id')->toArray();

        /** @var Collection<int, Collection<PestRoutesAppointment>> $recentAppointments */
        $recentAppointments = $this->getRecentAppointments($officeId, $subscriptionIds)->groupBy('subscriptionId');
        $regularSubscriptionIds = [];
        $initialFollowUpSubscriptionIds = [];
        $initialAppointments = new Collection();

        /** @var PestRoutesSubscription $subscription */
        foreach ($activeSubscriptions as $subscription) {
            $subscriptionRecentAppointments = $recentAppointments->get($subscription->id);

            if (empty($subscriptionRecentAppointments)) {
                $regularSubscriptionIds[] = $subscription->id;

                continue;
            }

            /** @var PestRoutesAppointment $mostRecentAppointment */
            $mostRecentAppointment = $subscriptionRecentAppointments->sortBy('start')->last();

            if (!$this->isInitial($mostRecentAppointment)) {
                continue;
            }

            if (
                $mostRecentAppointment->status === AppointmentStatus::Completed
                && Carbon::instance($mostRecentAppointment->start)->lessThan($initialFollowUpDueDate)
            ) {
                $initialFollowUpSubscriptionIds[] = $subscription->id;
                $initialAppointments->put($mostRecentAppointment->id, $mostRecentAppointment);
            }
        }

        $regularSubscriptions = $activeSubscriptions->filter(
            fn (PestRoutesSubscription $subscription) => in_array($subscription->id, $regularSubscriptionIds)
        );
        $lastCompleteAppointmentIds = $regularSubscriptions->pluck('lastAppointmentId')->filter()->toArray();
        $lastCompleteAppointments = $this->getAppointments($officeId, $lastCompleteAppointmentIds);

        $initialFollowUpSubscriptions = $activeSubscriptions->filter(
            fn (PestRoutesSubscription $subscription) => in_array($subscription->id, $initialFollowUpSubscriptionIds)
        );

        $customerIds = array_merge(
            $regularSubscriptions->pluck('customerId')->toArray(),
            $initialFollowUpSubscriptions->pluck('customerId')->toArray(),
        );

        $customers = $this->getCustomers($officeId, $customerIds);

        $regularPendingServices = $this->buildPendingServices($regularSubscriptions, $customers, $lastCompleteAppointments);
        $initialFollowUpPendingServices = $this->buildPendingServices($initialFollowUpSubscriptions, $customers, $initialAppointments);

        return $regularPendingServices->merge($initialFollowUpPendingServices);
    }

    /**
     * @param int[] $subscriptionIds
     *
     * @return Collection<PestRoutesSubscription>
     */
    private function getActiveSubscriptions(array $subscriptionIds): Collection
    {
        $activeSubscriptions = $this->subscriptionsDataProcessor->extractByIds($this->office->getId(), $subscriptionIds);

        return $this->removeSubscriptionsWithRecentlyCancelledAppointment($activeSubscriptions);
    }

    /**
     * @param Collection<PestRoutesSubscription> $activeSubscriptions
     *
     * @return Collection<PestRoutesSubscription>
     */
    private function removeSubscriptionsWithRecentlyCancelledAppointment(Collection $activeSubscriptions): Collection
    {
        $customerIds = $activeSubscriptions->pluck('customerId')->toArray();
        $cancellationThresholdDateStart = $this->date->clone()->subDays(self::CANCELLATION_PERIOD_DAYS)->startOfDay();
        $cancellationThresholdDateEnd = $this->date->clone()->addDays(self::CANCELLATION_PERIOD_DAYS)->endOfDay();

        $cancelledAppointments = $this->appointmentsDataProcessor->extract(
            $this->office->getId(),
            new SearchAppointmentsParams(
                officeId: $this->office->getId(),
                status: [AppointmentStatus::Cancelled],
                customerIds: $customerIds,
                date: DateFilter::between($cancellationThresholdDateStart, $cancellationThresholdDateEnd),
            )
        );
        $customerIdsWithCancelledAppointments = $cancelledAppointments->pluck('customerId');

        return $activeSubscriptions->reject(
            fn (PestRoutesSubscription $subscription) => $customerIdsWithCancelledAppointments->contains($subscription->customerId)
        );
    }

    /**
     * @param Collection<PestRoutesSubscription> $subscriptions
     * @param Collection<PestRoutesCustomer> $customers
     * @param Collection<PestRoutesAppointment> $appointments
     *
     * @return Collection<PendingService>
     */
    private function buildPendingServices(
        Collection $subscriptions,
        Collection $customers,
        Collection $appointments
    ): Collection {
        $pendingServices = new Collection();

        /** @var PestRoutesSubscription $subscription */
        foreach ($subscriptions as $subscription) {
            $pestRoutesCustomer = $customers->get($subscription->customerId);

            if ($pestRoutesCustomer === null) {
                // Customer is out of serviceable area or has pending cancellation
                continue;
            }

            $customer = $this->customerTranslator->toDomain($pestRoutesCustomer);
            $prevAppointment = $this->buildDomainAppointment(
                $appointments->get($subscription->lastAppointmentId),
                $pestRoutesCustomer
            );

            $pendingServices->add(new PendingService(
                subscriptionId: $subscription->id,
                plan: $this->plan,
                customer: $customer,
                previousAppointment: $prevAppointment,
                nextServiceDate: $this->date,
                customerPreferences: $this->customerPreferencesTranslator->toDomain($subscription),
            ));
        }

        return $pendingServices;
    }

    private function isInitial(PestRoutesAppointment $appointment): bool
    {
        /** @var PestRoutesServiceType|null $serviceStatus */
        $serviceStatus = $this->serviceTypes->get($appointment->serviceTypeId);

        return
            !empty($serviceStatus)
            && str_contains(Str::lower($serviceStatus->description), Str::lower(self::INITIAL_APPOINTMENT));
    }

    /**
     * @param int $officeId
     * @param int[] $customerIds
     *
     * @return Collection<PestRoutesCustomer>
     */
    private function getCustomers(int $officeId, array $customerIds): Collection
    {
        if (empty($customerIds)) {
            return collect();
        }

        return $this->customersDataProcessor->extract(
            $officeId,
            new SearchCustomersParams(
                ids: $customerIds,
                officeIds: [$officeId],
                includeCancellationReason: false,
                includeSubscriptions: false,
                includeCustomerFlag: false,
                includeAdditionalContacts: false,
                includePortalLogin: false,
            )
        )
            ->filter(fn (PestRoutesCustomer $customer) => $this->isInServiceableArea($customer) && !$this->isCustomerInPendingCancellation($customer))
            ->keyBy('id');
    }

    /**
     * @param int $officeId
     * @param int[] $subscriptionIds
     *
     * @return Collection<PestRoutesAppointment>
     */
    private function getRecentAppointments(int $officeId, array $subscriptionIds): Collection
    {
        if (empty($subscriptionIds)) {
            return collect();
        }

        $dueDate = $this->getPlanDueDate();

        return $this->appointmentsDataProcessor->extract(
            $officeId,
            new SearchAppointmentsParams(
                officeId: $officeId,
                status: [AppointmentStatus::Pending, AppointmentStatus::Completed],
                subscriptionIds: $subscriptionIds,
                date: DateFilter::greaterThanOrEqualTo($dueDate)
            )
        );
    }

    /**
     * @param int $officeId
     * @param int[] $appointmentIds
     *
     * @return Collection<int, PestRoutesAppointment>
     */
    private function getAppointments(int $officeId, array $appointmentIds): Collection
    {
        if (empty($appointmentIds)) {
            return collect();
        }

        return $this->appointmentsDataProcessor->extract(
            $officeId,
            new SearchAppointmentsParams(
                officeId: $officeId,
                ids: $appointmentIds,
            )
        )->keyBy('id');
    }

    private function buildDomainAppointment(PestRoutesAppointment $appointment, PestRoutesCustomer $customer): Appointment
    {
        return $this->appointmentTranslator->toDomain(
            $appointment,
            $this->serviceTypes->get($appointment->serviceTypeId),
            $customer
        );
    }

    private function isInServiceableArea(PestRoutesCustomer $customer): bool
    {
        return $this->office->isLocationInServiceableArea(new Coordinate($customer->latitude, $customer->longitude));
    }

    private function isCustomerInPendingCancellation(PestRoutesCustomer $customer): bool
    {
        return $customer->pendingCancel;
    }
}
