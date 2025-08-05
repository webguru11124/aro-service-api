<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities;

use App\Domain\Tracking\ValueObjects\FleetRouteSummary;
use App\Domain\Tracking\ValueObjects\TreatmentStateIdentity;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TreatmentState
{
    /** @var Collection<ServicedRoute> */
    private Collection $servicedRoutes;

    public function __construct(
        private readonly TreatmentStateIdentity $id,
        Collection $servicedRoutes,
        Collection $trackingData,
        Collection $drivingStats,
    ) {
        $this->servicedRoutes = $servicedRoutes;
        $this->mergeData($trackingData, $drivingStats);
    }

    private function mergeData(Collection $trackingData, Collection $drivingStats): void
    {
        foreach ($this->servicedRoutes as $servicedRoute) {
            $id = $servicedRoute->getServicePro()->getWorkdayId();
            $servicedRoute->setTrackingData(
                $trackingData->first(fn (RouteTrackingData $data) => $data->getId() === $id)
            );
            $servicedRoute->setDrivingStats(
                $drivingStats->first(fn (RouteDrivingStats $stats) => $stats->getId() === $id)
            );
        }
    }

    /**
     * @return TreatmentStateIdentity
     */
    public function getId(): TreatmentStateIdentity
    {
        return $this->id;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->id->date;
    }

    /**
     * @return Collection<ServicedRoute>
     */
    public function getServicedRoutes(): Collection
    {
        return $this->servicedRoutes;
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
        $historicTotalFuelConsumed = 0;
        $historicTotalMileage = 0;

        $totalAppointments = 0;
        $totalDriveTimeMinutes = 0;
        $totalServiceTimeMinutes = 0;
        $totalDriveMiles = 0;

        foreach ($this->getServicedRoutes() as $route) {
            $drivingStats = $route->getDrivingStats();

            if ($drivingStats) {
                $historicTotalFuelConsumed += $drivingStats->getHistoricFuelConsumption();
                $historicTotalMileage += $drivingStats->getHistoricVehicleMileage()->getMiles();
            }

            $routeStats = $route->getRouteStats();

            if ($routeStats) {
                $totalAppointments += $route->getRouteStats()->getTotalAppointments();
                $totalDriveTimeMinutes += $route->getRouteStats()->getTotalDriveTime()->getTotalMinutes();
                $totalDriveMiles += $route->getRouteStats()->getTotalDriveDistance()->getMiles();
                $totalServiceTimeMinutes += $route->getRouteStats()->getTotalServiceTime()->getTotalMinutes();
            }
        }

        $averageFuelConsumption = $historicTotalMileage > 0 ? $historicTotalFuelConsumed / $historicTotalMileage : 0.0;
        $fuelConsumptionForAppointments = $totalDriveMiles * $averageFuelConsumption;
        $appointmentsPerGallon = $fuelConsumptionForAppointments > 0
            ? $totalAppointments / $fuelConsumptionForAppointments
            : 0.0;

        return [
            'total_routes' => $this->getServicedRoutes()->count(),
            'total_appointments' => $totalAppointments,
            'total_drive_time_minutes' => $totalDriveTimeMinutes,
            'total_drive_miles' => $totalDriveMiles,
            'total_service_time_minutes' => $totalServiceTimeMinutes,
            'appointments_per_gallon' => $appointmentsPerGallon,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getActual(): array
    {
        $totalFuelConsumption = 0;
        $totalAppointmentsActual = 0;
        $totalDriveTimeMinutesActual = 0;
        $totalDriveMilesActual = 0;
        $totalServiceTimeMinutesActual = 0;

        foreach ($this->getServicedRoutes() as $route) {
            $drivingStats = $route->getDrivingStats();
            $completionStats = $route->getCompletionStats();

            $totalFuelConsumption += $drivingStats?->getFuelConsumption() ?? 0.0;
            $totalAppointmentsActual += $completionStats->getTotalAppointments();
            $totalDriveTimeMinutesActual += $drivingStats?->getTotalDriveTime()->getTotalMinutes() ?? 0;
            $totalDriveMilesActual += $drivingStats?->getTotalDriveDistance()->getMiles() ?? 0;
            $totalServiceTimeMinutesActual += $completionStats->getTotalServiceTime()->getTotalMinutes();
        }

        $appointmentsPerGallon = $totalFuelConsumption > 0 ? $totalAppointmentsActual / $totalFuelConsumption : 0.0;

        return [
            'total_routes_actual' => $this->getServicedRoutes()->count(),
            'total_appointments_actual' => $totalAppointmentsActual,
            'total_drive_time_minutes_actual' => $totalDriveTimeMinutesActual,
            'total_drive_miles_actual' => $totalDriveMilesActual,
            'total_service_time_minutes_actual' => $totalServiceTimeMinutesActual,
            'appointments_per_gallon_actual' => $appointmentsPerGallon,
        ];
    }
}
