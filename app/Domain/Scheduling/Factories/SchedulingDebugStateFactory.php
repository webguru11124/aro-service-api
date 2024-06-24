<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Factories;

use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\Contracts\Queries\PlansQuery;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\Customer;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\Plan;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\ValueObjects\CustomerPreferences;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SchedulingDebugStateFactory
{
    private CarbonInterface $date;
    private Office $office;

    private SchedulingState $schedulingState;
    /** @var Collection<Plan> */
    private Collection $plans;

    /** @var mixed[] */
    private array $sourceStateData;

    public function __construct(
        private PlansQuery $plansQuery,
        private GetOfficeQuery $officeQuery,
    ) {
    }

    /**
     * Creates scheduling state for a given date and office
     *
     * @param mixed[] $state
     *
     * @return SchedulingState
     */
    public function create(array $state): SchedulingState
    {
        $this->sourceStateData = $state;
        $this->office = $this->officeQuery->get($state['office_id']);
        $this->date = Carbon::parse($state['as_of_date'], $this->office->getTimezone());

        $this->buildSchedulingState();
        $this->resolvePlans();
        $this->resolveScheduledRoutes();

        return $this->schedulingState;
    }

    private function buildSchedulingState(): void
    {
        $this->schedulingState = new SchedulingState(
            $this->sourceStateData['id'],
            $this->date,
            $this->office
        );
    }

    private function resolvePlans(): void
    {
        $this->plans = $this->plansQuery->get()->keyBy(
            fn (Plan $plan) => $plan->getServiceTypeId()
        );
    }

    private function resolveScheduledRoutes(): void
    {
        $routes = collect();
        $pendingServices = collect();

        $this->addPendingServices($pendingServices, $this->sourceStateData['pending_services']);

        foreach ($this->sourceStateData['scheduled_routes'] as $route) {
            $servicePro = $this->buildServicePro($route);
            $scheduledRoute = new ScheduledRoute(
                $route['id'],
                $this->office->getId(),
                Carbon::parse($route['details']['date'], $this->office->getTimezone()),
                $servicePro,
                !empty($route['details']['route_type']) ? RouteType::tryFrom($route['details']['route_type']) : RouteType::REGULAR_ROUTE,
                $route['details']['actual_capacity'] ?? 0,
            );

            $this->addAppointments($scheduledRoute, $route['appointments']);
            $this->addPendingServices($pendingServices, $route['pending_services']);
            $routes->add($scheduledRoute);
        }

        $this->schedulingState->addScheduledRoutes($routes);
        $this->schedulingState->addPendingServices($pendingServices);
    }

    /**
     * @param ScheduledRoute $scheduledRoute
     * @param mixed[] $appointments
     *
     * @return void
     */
    private function addAppointments(ScheduledRoute $scheduledRoute, array $appointments): void
    {
        foreach ($appointments as $appointment) {
            $customer = $this->buildCustomer($appointment);
            $scheduledRoute->addAppointment($this->buildAppointment($appointment, $customer));
        }
    }

    /**
     * @param Collection<PendingService> $pendingServices
     * @param mixed[] $servicesData
     *
     * @return void
     */
    private function addPendingServices(Collection $pendingServices, array $servicesData): void
    {
        foreach ($servicesData as $datum) {
            $customer = $this->buildCustomer($datum);
            /** @var Plan $plan */
            $plan = $this->plans->get($datum['subscription']['service_type_id']);

            $pendingServices->add(new PendingService(
                subscriptionId: $datum['subscription']['id'],
                plan: $plan,
                customer: $customer,
                previousAppointment: $this->buildAppointment($datum['previous_appointment'], $customer),
                nextServiceDate: Carbon::parse($datum['next_service_date'], $this->office->getTimezone()),
                customerPreferences: new CustomerPreferences(
                    preferredStart: $datum['customer_preferences']['start'],
                    preferredEnd: $datum['customer_preferences']['end'],
                    preferredEmployeeId: $datum['customer_preferences']['employee_id'],
                    preferredDay: $datum['customer_preferences']['day'],
                ),
                nextAppointment: $this->buildAppointment($datum['next_appointment'], $customer),
            ));
        }
    }

    /**
     * @param mixed[] $route
     *
     * @return ServicePro
     */
    private function buildServicePro(array $route): ServicePro
    {
        $servicePro = new ServicePro(
            $route['service_pro']['id'],
            $route['service_pro']['name'],
            new Coordinate($route['service_pro']['location']['lat'], $route['service_pro']['location']['lng']),
            new Coordinate($route['service_pro']['location']['lat'], $route['service_pro']['location']['lng']),
            new TimeWindow($this->date->clone()->setTimeFromTimeString('08:00:00'), $this->date->clone()->setTimeFromTimeString('18:00:00')),
            $route['service_pro']['workday_id'] ?? null,
        );

        if ($servicePro->getName() != '#Reschedule Route#') {
            $servicePro->addSkills([new Skill(Skill::INI)]);
        }

        return $servicePro;
    }

    /**
     * @param mixed[] $appointment
     * @param Customer $customer
     *
     * @return Appointment
     */
    private function buildAppointment(array $appointment, Customer $customer): Appointment
    {
        return new Appointment(
            id: $appointment['id'],
            initial: $appointment['initial'] ?? false,
            date: Carbon::parse($appointment['date'], $this->office->getTimezone()),
            dateCompleted: !empty($appointment['date_completed']) ? Carbon::parse($appointment['date_completed'], $this->office->getTimezone()) : null,
            customer: $customer,
            duration: Duration::fromMinutes($appointment['duration'] ?? (!empty($appointment['initial']) ? 60 : 30)),
        );
    }

    /**
     * @param mixed[] $data
     *
     * @return Customer
     */
    private function buildCustomer(array $data): Customer
    {
        return new Customer(
            id: $data['customer']['id'],
            name: $data['customer']['name'],
            location: new Coordinate($data['location']['lat'], $data['location']['lng']),
            email: $data['customer']['email'] ?? null,
            preferredTechId: $data['customer_preferences']['employee_id'] ?? null,
        );
    }
}
