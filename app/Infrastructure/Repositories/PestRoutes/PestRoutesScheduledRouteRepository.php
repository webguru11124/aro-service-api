<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use App\Domain\Contracts\Repositories\ScheduledRouteRepository;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceProTranslator;
use App\Infrastructure\Services\PestRoutes\Scopes\PestRoutesBlockedSpotReasons;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;

class PestRoutesScheduledRouteRepository implements ScheduledRouteRepository
{
    use RouteResolverTrait;

    private const DEFAULT_DURATION_MIN = 30;
    private const APPOINTMENT_CREATED_NOTE = 'Scheduled by ARO on %s';

    /** @var Collection<PestRoutesRoute> */
    private Collection $routes;
    /** @var Collection<PestRoutesEmployee> */
    private Collection $employees;
    private Office $office;
    private CarbonInterface $date;
    /** @var Collection<PestRoutesServiceType> */
    private Collection $serviceTypes;
    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;
    /** @var Collection<PestRoutesSpot> */
    private Collection $spots;
    /** @var Collection<PestRoutesCustomer> */
    private Collection $customers;

    public function __construct(
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
        private readonly PestRoutesEmployeesDataProcessor $employeesDataProcessor,
        private readonly SpotsDataProcessor $spotsDataProcessor,
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly ServiceTypesDataProcessor $serviceTypeDataProcessor,
        private readonly PestRoutesCustomersDataProcessor $customersDataProcessor,
        private readonly PestRoutesServiceProTranslator $serviceProTranslator,
        private readonly PestRoutesSchedulingAppointmentTranslator $appointmentTranslator,
    ) {
    }

    /**
     * Persists scheduled route
     *
     * @param ScheduledRoute $scheduledRoute
     *
     * @return void
     * @throws InternalServerErrorHttpException
     */
    public function save(ScheduledRoute $scheduledRoute): void
    {
        /** @var PendingService $pendingService */
        foreach ($scheduledRoute->getPendingServices() as $pendingService) {
            $startTime = $scheduledRoute->getDate()->clone()->setTimeFromTimeString($pendingService->getPreferredStart())->toDateTime();
            $endTime = $scheduledRoute->getDate()->clone()->setTimeFromTimeString($pendingService->getPreferredEnd())->toDateTime();

            if ($pendingService->isRescheduled()) {
                $this->appointmentsDataProcessor->update($scheduledRoute->getOfficeId(), new UpdateAppointmentsParams(
                    appointmentId: $pendingService->getNextAppointment()->getId(),
                    routeId: $scheduledRoute->getId(),
                ));
            } else {
                $this->appointmentsDataProcessor->create($scheduledRoute->getOfficeId(), new CreateAppointmentsParams(
                    customerId: $pendingService->getCustomer()->getId(),
                    typeId: $pendingService->getServiceTypeId(),
                    start: $startTime,
                    end: $endTime,
                    duration: self::DEFAULT_DURATION_MIN,
                    employeeId: $scheduledRoute->getServicePro()->getId(),
                    notes: $this->getAppointmentNote($scheduledRoute->getDate()->timezone),
                    routeId: $scheduledRoute->getId(),
                    subscriptionId: $pendingService->getSubscriptionId(),
                    officeId: $scheduledRoute->getOfficeId(),
                ));
            }
        }
    }

    /**
     * Returns collection of scheduled routes
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<ScheduledRoute>
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     * @throws InternalServerErrorHttpException
     */
    public function findByOfficeIdAndDate(Office $office, CarbonInterface $date): Collection
    {
        $this->office = $office;
        $this->date = $date;

        $this->resolveRegularRoutes();
        $this->resolveEmployees();
        $this->removeInvalidRoutes();
        $this->resolveSpots();
        $this->removeRoutesWithoutSpots();
        $this->resolveAppointments();
        $this->resolveCustomers();
        $this->resolveAppointmentServiceTypes();

        return $this->buildScheduledRoutes();
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws NoRegularRoutesFoundException
     */
    private function resolveRegularRoutes(): void
    {
        $this->routes = $this->getRegularRoutes($this->office, $this->date, false);
    }

    /**
     * @throws NoServiceProFoundException
     * @throws InternalServerErrorHttpException
     */
    private function resolveEmployees(): void
    {
        $this->employees = collect();

        $employeeIds = $this->routes
            ->map(fn (PestRoutesRoute $route) => $route->assignedTech)
            ->filter(fn ($assignedTech) => $assignedTech !== null)
            ->unique()
            ->toArray();

        if (empty($employeeIds)) {
            throw NoServiceProFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }

        $this->employees = $this->employeesDataProcessor->extract(
            $this->office->getId(),
            new SearchEmployeesParams(
                officeIds: [$this->office->getId()],
                employeeIds: $employeeIds
            )
        )->keyBy('id');

        if ($this->employees->isEmpty()) {
            throw NoServiceProFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    /**
     * @throws NoRegularRoutesFoundException
     */
    private function removeInvalidRoutes(): void
    {
        $employeeIds = $this->employees->pluck('id')->toArray();
        $this->routes = $this->routes->filter(
            fn (PestRoutesRoute $route) => in_array($route->assignedTech, $employeeIds)
        );

        if ($this->routes->isEmpty()) {
            throw NoRegularRoutesFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    private function resolveAppointments(): void
    {
        $routeIds = $this->routes->pluck('id')->toArray();

        $this->appointments = $this->appointmentsDataProcessor->extract(
            $this->office->getId(),
            new SearchAppointmentsParams(
                officeIds: [$this->office->getId()],
                routeIds: $routeIds,
            )
        );
    }

    private function resolveSpots(): void
    {
        $routeIds = $this->routes->pluck('id')->toArray();

        $this->spots = $this->spotsDataProcessor->extract(
            $this->office->getId(),
            new SearchSpotsParams(
                officeIds: [$this->office->getId()],
                routeIds: $routeIds,
            )
        );
    }

    private function removeRoutesWithoutSpots(): void
    {
        $groupedSpots = $this->spots->groupBy('routeId');
        $this->routes = $this->routes->reject(
            fn (PestRoutesRoute $route) => is_null($groupedSpots->get($route->id))
        );
    }

    /**
     * @return Collection<ScheduledRoute>
     */
    private function buildScheduledRoutes(): Collection
    {
        $scheduledRoutes = new Collection();

        foreach ($this->routes as $route) {
            /** @var PestRoutesEmployee $employee */
            $employee = $this->employees->get($route->assignedTech);
            $routeSpots = $this->getRouteSpots($route->id);
            $routeAppointments = $this->getRouteAppointments($route->id);

            $scheduledRoute = new ScheduledRoute(
                id: $route->id,
                officeId: $route->officeId,
                date: Carbon::instance($route->date),
                servicePro: $this->serviceProTranslator->toDomain($route, $employee, $routeSpots),
                routeType: RouteType::fromString($route->title),
                actualCapacityCount: $routeSpots->count(),
            );

            /** @var PestRoutesAppointment $appointment */
            foreach ($routeAppointments as $appointment) {
                $customer = $this->customers->get($appointment->customerId);
                $scheduledRoute->addAppointment($this->buildDomainAppointment($appointment, $customer));
            }

            $scheduledRoutes->add($scheduledRoute);
        }

        return $scheduledRoutes;
    }

    /**
     * @param int $routeId
     *
     * @return Collection<PestRoutesSpot>
     */
    private function getRouteSpots(int $routeId): Collection
    {
        return $this->spots->filter(
            fn (PestRoutesSpot $spot) => $spot->routeId === $routeId
        )->reject(
            function (PestRoutesSpot $spot) {
                if ($spot->capacity > 0 || is_null($spot->blockReason)) {
                    return false;
                }

                $canBeProcessed = array_reduce(
                    PestRoutesBlockedSpotReasons::PROCESSABLE_ON_SCHEDULING,
                    function ($carry, $marker) use ($spot) {
                        return $carry || stripos($spot->blockReason, $marker) !== false;
                    },
                    false
                );

                return !$canBeProcessed;
            }
        )->sortBy('start');
    }

    /**
     * @param int $routeId
     *
     * @return Collection<PestRoutesAppointment>
     */
    private function getRouteAppointments(int $routeId): Collection
    {
        return $this->appointments->filter(
            fn (PestRoutesAppointment $appointment) => $appointment->routeId === $routeId
        );
    }

    private function buildDomainAppointment(PestRoutesAppointment $appointment, PestRoutesCustomer $customer): Appointment
    {
        return $this->appointmentTranslator->toDomain(
            $appointment,
            $this->serviceTypes->get($appointment->serviceTypeId),
            $customer
        );
    }

    private function resolveAppointmentServiceTypes(): void
    {
        $this->serviceTypes = $this->serviceTypeDataProcessor->extract(
            $this->office->getId(),
            new SearchServiceTypesParams(
                officeIds: [$this->office->getId()]
            )
        )->keyBy('id');
    }

    private function resolveCustomers(): void
    {
        $customerIds = $this->appointments->pluck('customerId')->toArray();

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

    private function getAppointmentNote(CarbonTimeZone $timeZone): string
    {
        $now = Carbon::now($timeZone);

        return sprintf(
            self::APPOINTMENT_CREATED_NOTE,
            $now->format('d/m/y H:i:s')
        );
    }
}
