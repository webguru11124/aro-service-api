<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use App\Domain\Contracts\Queries\PlansQuery;
use Aptive\PestRoutesSDK\Filters\NumberFilter;
use App\Domain\RouteOptimization\DomainContext;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use App\Domain\Contracts\Repositories\RescheduledPendingServiceRepository;
use App\Domain\RouteOptimization\ValueObjects\RouteGroupType;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\Plan;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoTechFoundException;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminderStatus;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\SearchAppointmentRemindersParams;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesSubscriptionsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerPreferencesTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerTranslator;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStatePersister;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription as PestRoutesSubscription;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PestRoutesRescheduledPendingServiceRepository implements RescheduledPendingServiceRepository
{
    private const SCHEDULING_PERIOD_DAYS = 14;

    private Office $office;
    private CarbonInterface $date;

    private int|null $rescheduleRouteTechId = null;

    /** @var Collection<Plan> */
    private Collection $plans;
    /** @var Collection<PestRoutesRoute> */
    private Collection $routes;
    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;
    /** @var Collection<PestRoutesServiceType> */
    private Collection $serviceTypes;
    /** @var Collection<PestRoutesCustomer> */
    private Collection $customers;
    /** @var Collection<PestRoutesSubscription> */
    private Collection $subscriptions;
    /** @var Collection<PestRoutesAppointment> */
    private Collection $prevAppointments;

    public function __construct(
        private readonly PlansQuery $plansQuery,
        private readonly PestRoutesEmployeesDataProcessor $employeesDataProcessor,
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
        private readonly PestRoutesAppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly ServiceTypesDataProcessor $serviceTypesDataProcessor,
        private readonly PestRoutesCustomersDataProcessor $customersDataProcessor,
        private readonly PestRoutesSubscriptionsDataProcessorCacheWrapper $subscriptionsDataProcessor,
        private readonly PestRoutesCustomerTranslator $customerTranslator,
        private readonly PestRoutesCustomerPreferencesTranslator $customerPreferencesTranslator,
        private readonly PestRoutesSchedulingAppointmentTranslator $appointmentTranslator,
        private readonly PestRoutesAppointmentRemindersDataProcessor $appointmentRemindersDataProcessor,
    ) {
    }

    /**
     * Returns collection of pending appointments for office and date
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<PendingService>
     * @throws NoTechFoundException
     */
    public function findByOfficeIdAndDate(Office $office, CarbonInterface $date): Collection
    {
        $this->office = $office;
        $this->date = $date;

        $this->resolvePlans();
        $this->resolveRescheduleRouteTechId();
        $this->resolveRoutes();
        $this->resolveAppointmentServiceTypes();
        $this->resolveAppointments();
        $this->resolveCustomers();
        $this->resolveSubscriptions();
        $this->resolvePrevAppointments();

        return $this->buildPendingServices();
    }

    private function resolvePlans(): void
    {
        $this->plans = $this->plansQuery->get();
    }

    private function getPlanByServiceId(int $serviceId): Plan|null
    {
        return $this->plans->first(fn (Plan $plan) => $plan->getServiceTypeId() === $serviceId);
    }

    /**
     * @throws NoTechFoundException
     */
    private function resolveRescheduleRouteTechId(): void
    {
        $searchParams = new SearchEmployeesParams(
            officeIds: [$this->office->getId()],
            isActive: true,
            lastName: PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME,
            firstName: PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME
        );

        /** @var PestRoutesEmployee $employee */
        $employee = $this->employeesDataProcessor->extract($this->office->getId(), $searchParams)->first();

        if (empty($employee)) {
            throw NoTechFoundException::instance(
                $this->office->getId(),
                $this->office->getName(),
                $this->date,
                PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME . ' ' . PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME,
            );
        }

        $this->rescheduleRouteTechId = $employee->id;
    }

    private function resolveRoutes(): void
    {
        $startDate = Carbon::tomorrow($this->office->getTimezone())->startOfDay();
        $endDate = $startDate->clone()->addDays(self::SCHEDULING_PERIOD_DAYS)->endOfDay();

        $routes = $this->routesDataProcessor->extract($this->office->getId(), new SearchRoutesParams(
            officeIds: [$this->office->getId()],
            dateStart: $startDate,
            dateEnd: $endDate,
            lockedRoute: false
        ));

        $this->routes = $routes
            ->filter(
                fn (PestRoutesRoute $route) => $this->isMatchingRegularRouteTitle($route->groupTitle)
            )->reject(
                fn (PestRoutesRoute $route) => RouteType::fromString($route->title) === RouteType::UNKNOWN
            );
    }

    private function isMatchingRegularRouteTitle(string $title): bool
    {
        return RouteGroupType::fromString($title) === RouteGroupType::REGULAR_ROUTE;
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

    private function resolveAppointments(): void
    {
        $routeIds = $this->routes->pluck('id')->toArray();

        if (empty($routeIds)) {
            $this->appointments = collect();

            return;
        }

        $appointments = $this->appointmentsDataProcessor->extract(
            $this->office->getId(),
            new SearchAppointmentsParams(
                officeId: $this->office->getId(),
                status: [AppointmentStatus::Pending],
                routeIds: $routeIds,
            )
        );

        if ($appointments->isEmpty()) {
            $this->appointments = collect();

            return;
        }

        $rescheduledAppointments = $this->getRescheduledAppointments($appointments);
        $unconfirmedAppointments = $this->getUnconfirmedAppointments($appointments);

        $this->appointments = $rescheduledAppointments->merge($unconfirmedAppointments);
    }

    private function getRescheduledAppointments(Collection $appointments): Collection
    {
        $rescheduledRouteIds = $this->getRescheduledRouteIds();

        return $appointments->filter(
            fn (PestRoutesAppointment $appointment) => $rescheduledRouteIds->contains($appointment->routeId),
        );
    }

    private function getUnconfirmedAppointments(Collection $appointments): Collection
    {
        $routeIds = $this->getEligibleRouteIdsForUnconfirmedAppointments();
        $appointmentIds = $appointments->filter(
            fn (PestRoutesAppointment $appointment) => $routeIds->contains($appointment->routeId)
        )->pluck('id');
        $appointmentReminders = $this->getAppointmentReminders($appointmentIds->toArray());

        $unconfirmedAppointmentIds = $appointmentIds->filter(
            fn (int $appointmentId) => empty($appointmentReminders->get($appointmentId))
        );

        return $appointments->filter(
            fn (PestRoutesAppointment $appointment) => $unconfirmedAppointmentIds->contains($appointment->id),
        );
    }

    private function getEligibleRouteIdsForUnconfirmedAppointments(): Collection
    {
        $minStartDate = $this->date->clone()
            ->addDays(DomainContext::getMinDaysToAllowRescheduleUnconfirmedAppointments());
        $rescheduledRouteIds = $this->getRescheduledRouteIds();

        return $this->routes->filter(
            fn (PestRoutesRoute $route) => Carbon::instance($route->date)->gte($minStartDate) && !$rescheduledRouteIds->contains($route->id),
        )->pluck('id');
    }

    /**
     * @param array<int> $appointmentIds
     *
     * @return Collection
     * @throws InternalServerErrorHttpException
     */
    private function getAppointmentReminders(array $appointmentIds): Collection
    {
        return $this->appointmentRemindersDataProcessor->extract(
            $this->office->getId(),
            new SearchAppointmentRemindersParams(
                appointmentId: NumberFilter::in($appointmentIds),
                status: NumberFilter::in([
                    AppointmentReminderStatus::CONFIRMED_BY_OFFICE->value,
                    AppointmentReminderStatus::CONFIRMED_VIA_SMS->value,
                ])
            )
        )->groupBy('appointmentId');
    }

    private function getRescheduledRouteIds(): Collection
    {
        return $this->routes
            ->filter(fn (PestRoutesRoute $route) => $route->assignedTech === $this->rescheduleRouteTechId)
            ->pluck('id');
    }

    private function resolveCustomers(): void
    {
        $customerIds = $this->appointments->pluck('customerId')->unique()->toArray();

        if (empty($customerIds)) {
            $this->customers = collect();

            return;
        }

        $this->customers = $this->customersDataProcessor->extract(
            $this->office->getId(),
            new SearchCustomersParams(
                ids: $customerIds,
                officeIds: [$this->office->getId()],
                includeCancellationReason: false,
                includeSubscriptions: false,
                includeCustomerFlag: false,
                includeAdditionalContacts: false,
                includePortalLogin: false,
            )
        )->keyBy('id');
    }

    private function resolveSubscriptions(): void
    {
        $subscriptionIds = $this->appointments->pluck('subscriptionId')->unique()->toArray();

        if (empty($subscriptionIds)) {
            $this->subscriptions = collect();

            return;
        }

        $this->subscriptions = $this->subscriptionsDataProcessor->extract(
            $this->office->getId(),
            new SearchSubscriptionsParams(
                ids: $subscriptionIds
            )
        )->keyBy('id');
    }

    private function resolvePrevAppointments(): void
    {
        $appointmentIds = $this->subscriptions->pluck('lastAppointmentId')->toArray();

        if (empty($appointmentIds)) {
            $this->prevAppointments = collect();

            return;
        }

        $this->prevAppointments = $this->appointmentsDataProcessor->extract(
            $this->office->getId(),
            new SearchAppointmentsParams(
                officeId: $this->office->getId(),
                ids: $appointmentIds,
            )
        )->keyBy('subscriptionId');
    }

    /**
     * @return Collection<PendingService>
     */
    private function buildPendingServices(): Collection
    {
        $pendingServices = new Collection();

        foreach ($this->appointments as $appointment) {
            /** @var PestRoutesSubscription $subscription */
            $subscription = $this->subscriptions->get($appointment->subscriptionId);

            if (!$subscription) { // reservice
                continue;
            }

            $plan = $this->getPlanByServiceId($subscription->serviceId);

            if (!$plan) { // unsupported plan
                continue;
            }

            /** @var PestRoutesAppointment $prevAppointment */
            $prevAppointment = $this->prevAppointments->get($appointment->subscriptionId);

            if (!$prevAppointment) { // initial
                continue;
            }

            /** @var PestRoutesCustomer $customer */
            $customer = $this->customers->get($appointment->customerId);

            $pendingServices->add($this->buildPendingService(
                $plan,
                $prevAppointment,
                $appointment,
                $subscription,
                $customer
            ));
        }

        return $pendingServices;
    }

    private function buildDomainAppointment(PestRoutesAppointment $appointment, PestRoutesCustomer $customer): Appointment
    {
        return $this->appointmentTranslator->toDomain(
            $appointment,
            $this->serviceTypes->get($appointment->serviceTypeId),
            $customer
        );
    }

    private function buildPendingService(
        Plan $plan,
        PestRoutesAppointment $prevAppointment,
        PestRoutesAppointment $nextAppointment,
        PestRoutesSubscription $subscription,
        PestRoutesCustomer $customer,
    ): PendingService {
        return new PendingService(
            subscriptionId: $subscription->id,
            plan: $plan,
            customer: $this->customerTranslator->toDomain($customer),
            previousAppointment: $this->buildDomainAppointment($prevAppointment, $customer),
            nextServiceDate: $this->date,
            customerPreferences: $this->customerPreferencesTranslator->toDomain($subscription),
            nextAppointment: $this->buildDomainAppointment($nextAppointment, $customer)
        );
    }
}
