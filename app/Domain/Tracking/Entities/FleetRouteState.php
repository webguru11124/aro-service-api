<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities;

use App\Domain\Tracking\ValueObjects\FleetRouteSummary;
use App\Domain\Tracking\ValueObjects\OptimizationStateMetrics;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FleetRouteState
{
    /** @var Collection<FleetRoute> */
    private Collection $fleetRoutes;

    public function __construct(
        private readonly int $id,
        private readonly int $officeId,
        private readonly CarbonInterface $date,
        private readonly CarbonInterface|null $updatedAt = null,
        private OptimizationStateMetrics|null $metrics = null,
    ) {
        $this->fleetRoutes = new Collection();
    }

    /**
     * @param FleetRoute $fleetRoute
     *
     * @return FleetRouteState
     */
    public function addFleetRoute(FleetRoute $fleetRoute): self
    {
        $this->fleetRoutes->add($fleetRoute);

        return $this;
    }

    /**
     * @return Collection<FleetRoute>
     */
    public function getFleetRoutes(): Collection
    {
        return $this->fleetRoutes;
    }

    /**
     * @return OptimizationStateMetrics|null
     */
    public function getMetrics(): OptimizationStateMetrics|null
    {
        return $this->metrics;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * Returns summary of fleet routes
     *
     * @return FleetRouteSummary
     */
    public function getSummary(): FleetRouteSummary
    {
        $planned = $this->getPlanned();
        $actual = $this->getActual();

        return new FleetRouteSummary(
            $planned['total_routes'],
            $planned['total_appointments'],
            $planned['total_drive_time_minutes'],
            $planned['total_drive_miles'],
            $planned['total_service_time_minutes'],
            $planned['appointments_per_gallon'],
            $actual['total_routes_actual'],
            $actual['total_appointments_actual'],
            $actual['total_drive_time_minutes_actual'],
            $actual['total_drive_miles_actual'],
            $actual['total_service_time_minutes_actual'],
            $actual['appointments_per_gallon_actual']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getPlanned(): array
    {
        $historicTotalFuelConsumed = $this->getFleetRoutes()->sum(
            fn (FleetRoute $route) => $route->getDrivingStats()?->getHistoricFuelConsumption()
        );
        $historicTotalMileage = $this->getFleetRoutes()->sum(
            fn (FleetRoute $route) => $route->getDrivingStats()?->getHistoricVehicleMileage()->getMiles()
        );
        $averageFuelConsumption = $historicTotalMileage > 0 ? $historicTotalFuelConsumed / $historicTotalMileage : 0.0;
        $totalDriveMiles = $this->getFleetRoutes()->sum(
            fn (FleetRoute $route) => $route->getRouteStats()->getTotalDriveDistance()->getMiles()
        );
        $fuelConsumptionForAppointments = $totalDriveMiles * $averageFuelConsumption;
        $totalAppointments = $this->getFleetRoutes()->sum(
            fn (FleetRoute $route) => $route->getRouteStats()->getTotalAppointments()
        );
        $appointmentsPerGallon = $fuelConsumptionForAppointments > 0 ? $totalAppointments / $fuelConsumptionForAppointments
            : 0.0;

        return [
            'total_routes' => $this->getFleetRoutes()->count(),
            'total_appointments' => $totalAppointments,
            'total_drive_time_minutes' => $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getRouteStats()->getTotalDriveTime()->getTotalMinutes()),
            'total_drive_miles' => $totalDriveMiles,
            'total_service_time_minutes' => $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getRouteStats()->getTotalServiceTime()->getTotalMinutes()),
            'appointments_per_gallon' => $appointmentsPerGallon,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getActual(): array
    {
        $totalFuelConsumption = $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getDrivingStats()?->getFuelConsumption());
        $totalAppointmentsActual = $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getCompletionStats()?->getTotalAppointments());
        $appointmentsPerGallon = $totalFuelConsumption > 0 ? $totalAppointmentsActual / $totalFuelConsumption : 0.0;

        return [
            'total_routes_actual' => $this->getFleetRoutes()->count(),
            'total_appointments_actual' => $totalAppointmentsActual,
            'total_drive_time_minutes_actual' => $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getDrivingStats()?->getTotalDriveTime()->getTotalMinutes()),
            'total_drive_miles_actual' => $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getDrivingStats()?->getTotalDriveDistance()->getMiles()),
            'total_service_time_minutes_actual' => $this->getFleetRoutes()->sum(fn (FleetRoute $route) => $route->getCompletionStats()?->getTotalServiceTime()?->getTotalMinutes()),
            'appointments_per_gallon_actual' => $appointmentsPerGallon,
        ];
    }

    /**
     * @return CarbonInterface|null
     */
    public function getUpdatedAt(): CarbonInterface|null
    {
        return $this->updatedAt;
    }

    /**
     * It checks if the stats can be updated
     *
     * @return bool
     */
    public function canUpdateStats(): bool
    {
        $nowInOffice = Carbon::now($this->getDate()->getTimezone());

        if ($this->date->greaterThanOrEqualTo(Carbon::tomorrow($this->date->getTimezone()))) {
            return false;
        }

        return
            is_null($this->getUpdatedAt()) // never updated
            || $this->getUpdatedAt()->isSameDay($this->date) // updated on day of service
            || $this->date->isSameDay($nowInOffice); // updated today
    }

    /**
     * It returns fleet route by route ID
     *
     * @param int $routeId
     *
     * @return FleetRoute|null
     */
    public function getFleetRouteById(int $routeId): FleetRoute|null
    {
        return $this->getFleetRoutes()->first(fn (FleetRoute $route) => $route->getId() === $routeId);
    }
}
