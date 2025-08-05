<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use App\Domain\Contracts\Repositories\FleetRouteStateRepository;
use App\Domain\Contracts\Repositories\ServicedRoutesRepository;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\Entities\ScheduledAppointment;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceProTranslator;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

// TODO: Add unit tests for this class
class PestRoutesServicedRoutesRepository implements ServicedRoutesRepository
{
    use RouteResolverTrait;

    private const RESCHEDULE_ROUTE_EMPLOYEE_NAME = '#Reschedule';

    private Office $office;
    private FleetRouteState|null $fleetRouteState;

    /** @var Collection<PestRoutesRoute> */
    private Collection $routes;
    /** @var Collection<PestRoutesEmployee> */
    private Collection $employees;
    private CarbonInterface $date;
    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;
    /** @var Collection<PestRoutesSpot> */
    private Collection $spots;
    /** @var Collection<PestRoutesCustomer> */
    private Collection $customers;

    // TODO: Add caching for routes, appointments, spots, customers
    public function __construct(
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
        private readonly EmployeesDataProcessor $employeesDataProcessor,
        private readonly SpotsDataProcessor $spotsDataProcessor,
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly PestRoutesServiceProTranslator $serviceProTranslator,
        private readonly PestRoutesCustomersDataProcessor $customersDataProcessor,
        private readonly FleetRouteStateRepository $fleetRouteStateRepository,
    ) {
    }

    /**
     * Returns serviced routes for specified office and date
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<ServicedRoute>
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     * @throws InternalServerErrorHttpException
     */
    public function findByOfficeAndDate(Office $office, CarbonInterface $date): Collection
    {
        $this->office = $office;
        $this->date = $date;

        $this->resolveOptimizationState();
        $this->resolveRegularRoutes();
        $this->resolveEmployees();
        $this->removeInvalidRoutes();
        $this->resolveSpots();
        $this->removeRoutesWithoutSpots();
        $this->resolveAppointments();
        $this->resolveCustomers();

        return $this->buildServicedRoutes();
    }

    /**
     * @throws InternalServerErrorHttpException
     * @throws NoRegularRoutesFoundException
     */
    private function resolveRegularRoutes(): void
    {
        $this->routes = $this->getRegularRoutes($this->office, $this->date);
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
                isActive: true,
                employeeIds: $employeeIds
            )
        );

        // We have to get all employees, even inactive ones
        $this->employees = $this->employees->merge(
            $this->employeesDataProcessor->extract(
                $this->office->getId(),
                new SearchEmployeesParams(
                    officeIds: [$this->office->getId()],
                    isActive: false,
                    employeeIds: $employeeIds
                )
            )
        );

        $this->employees = $this->employees->reject(fn (PestRoutesEmployee $employee) => $employee->firstName === self::RESCHEDULE_ROUTE_EMPLOYEE_NAME)
            ->keyBy('id');

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

        $this->appointments = $this->appointmentsDataProcessor->extract($this->office->getId(), new SearchAppointmentsParams(
            officeIds: [$this->office->getId()],
            status: [AppointmentStatus::Pending, AppointmentStatus::Completed, AppointmentStatus::NoShow],
            routeIds: $routeIds,
        ));
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

    private function resolveOptimizationState(): void
    {
        $this->fleetRouteState = $this->fleetRouteStateRepository->findByOfficeIdAndDate($this->office->getId(), $this->date);
    }

    /**
     * @return Collection<ServicedRoute>
     */
    private function buildServicedRoutes(): Collection
    {
        $scheduledRoutes = new Collection();

        /** @var PestRoutesRoute $route */
        foreach ($this->routes as $route) {
            $fleetRoute = $this->fleetRouteState?->getFleetRouteById($route->id);

            $servicedRoute = new ServicedRoute(
                id: $route->id,
                servicePro: $this->buildServicePro($route),
                routeStats: $fleetRoute?->getRouteStats(),
                geometry: $fleetRoute?->getRouteGeometry(),
            );

            $plannedEvents = $fleetRoute?->getRoute() ?? collect();

            foreach ($plannedEvents as $event) {
                $servicedRoute->addPlannedEvent($event);
            }

            $routeAppointments = $this->appointments->filter(
                fn (PestRoutesAppointment $appointment) => $appointment->routeId === $route->id
            );

            /** @var PestRoutesAppointment $appointment */
            foreach ($routeAppointments as $appointment) {
                $plannedAppointment = $plannedEvents->first(
                    fn (FleetRouteEvent $event) => $event->getId() === $appointment->id
                );

                $servicedRoute->addScheduledAppointment($this->buildDomainAppointment($appointment, $plannedAppointment));
            }

            $scheduledRoutes->add($servicedRoute);
        }

        return $scheduledRoutes;
    }

    private function buildServicePro(PestRoutesRoute $route): ServicePro
    {
        /** @var PestRoutesEmployee $employee */
        $employee = $this->employees->get($route->assignedTech);
        $routeSpots = $this->spots->filter(
            fn (PestRoutesSpot $spot) => $spot->routeId === $route->id
        );

        return $this->serviceProTranslator->toDomain($route, $employee, $routeSpots);
    }

    private function buildDomainAppointment(PestRoutesAppointment $appointment, FleetRouteEvent|null $event): ScheduledAppointment
    {
        $customer = $this->customers->get($appointment->customerId);

        return new ScheduledAppointment(
            id: $appointment->id,
            date: Carbon::instance($appointment->start),
            serviceTimeWindow: !empty($appointment->dateCompleted) ? $this->buildServiceTimeWindow($appointment) : null,
            expectedTimeWindow: $event?->getTimeWindow(),
            dateComplete: !empty($appointment->dateCompleted) ? Carbon::instance($appointment->dateCompleted) : null,
            customer: $this->buildDomainCustomer($customer),
        );
    }

    private function buildServiceTimeWindow(PestRoutesAppointment $appointment): TimeWindow
    {
        return new TimeWindow(
            Carbon::instance($appointment->timeIn),
            Carbon::instance($appointment->timeOut)
        );
    }

    private function buildDomainCustomer(PestRoutesCustomer $customer): \App\Domain\SharedKernel\Entities\Customer
    {
        return new \App\Domain\SharedKernel\Entities\Customer(
            id: $customer->id,
            name: $customer->firstName . ' ' . $customer->lastName,
            location: new Coordinate(
                latitude: $customer->latitude,
                longitude: $customer->longitude
            ),
            address: new Address(
                address: $customer->address->address,
                city: $customer->address->city,
                state: $customer->address->state,
                zip: $customer->address->zip,
            ),
            phone: $this->getCustomerPhone($customer),
        );
    }

    private function getCustomerPhone(PestRoutesCustomer $customer): string
    {
        $phone = '';

        foreach ($customer->phones as $customerPhone) {
            if ($customerPhone->isPrimary) {
                $phone = $customerPhone->phone;

                break;
            }
        }

        return $phone;
    }
}
