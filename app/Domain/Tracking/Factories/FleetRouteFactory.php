<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\RouteOptimization\Factories\RouteStatsFactory;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class FleetRouteFactory
{
    private const RESCHEDULE_ROUTE_EMPLOYEE_NAME = '#Reschedule Route#';

    public function __construct(
        private RouteFactory $routeFactory,
        private RouteStatsFactory $routeStatsFactory,
        private ServiceProFactory $serviceProFactory,
    ) {
    }

    /**
     * Create a fleet route from route data and route stats
     *
     * @param array<string, mixed> $routeData
     * @param CarbonTimeZone $timeZone
     *
     * @return FleetRoute|null
     */
    public function create(array $routeData, CarbonTimeZone $timeZone): FleetRoute|null
    {
        if (empty($routeData['schedule']) || $routeData['service_pro']['name'] == self::RESCHEDULE_ROUTE_EMPLOYEE_NAME) {
            return null;
        }

        $routeId = $routeData['route_id'];
        $routeDate = Carbon::createFromDate($routeData['details']['start_at']);

        $fleetRoute = new FleetRoute(
            $routeId,
            $routeDate,
            $this->serviceProFactory->create($routeData, $timeZone),
            $this->routeStatsFactory->create($routeData['route_stats']),
            $routeData['geometry'] ?? null,
        );

        if (!empty($routeData['actual_stats'])) {
            $fleetRoute->setDrivingStats(
                $this->createRouteDrivingStats(
                    $routeData['service_pro']['workday_id'] ?? '',
                    $routeData['actual_stats']
                )
            );
            $fleetRoute->setCompletionStats($this->createRouteCompletionStats($routeData['actual_stats']));
        }

        $fleetRoute->setRoute($this->routeFactory->create($routeData['schedule'], $timeZone));

        return $fleetRoute;
    }

    /**
     * @param string $id
     * @param array<string, mixed> $statsData
     *
     * @return RouteDrivingStats
     */
    private function createRouteDrivingStats(string $id, array $statsData): RouteDrivingStats
    {
        return new RouteDrivingStats(
            id: $id,
            totalDriveTime: Duration::fromMinutes($statsData['total_drive_time_minutes'] ?? 0),
            totalDriveDistance: Distance::fromMiles($statsData['total_drive_miles'] ?? 0),
            averageDriveTimeBetweenServices: Duration::fromMinutes($statsData['average_drive_time_minutes'] ?? 0),
            averageDriveDistanceBetweenServices: Distance::fromMiles($statsData['average_drive_miles'] ?? 0),
            totalWorkingTime: Duration::fromMinutes($statsData['total_working_time_minutes'] ?? 0),
            fuelConsumption: $statsData['fuel_consumption'] ?? 0,
            historicVehicleMileage: Distance::fromMiles($statsData['historic_vehicle_mileage'] ?? 0),
            historicFuelConsumption: $statsData['historic_fuel_consumption'] ?? 0,
        );
    }

    /**
     * @param array<string, mixed> $statsData
     *
     * @return RouteCompletionStats
     */
    private function createRouteCompletionStats(array $statsData): RouteCompletionStats
    {
        return new RouteCompletionStats(
            routeAdherence: $statsData['route_adherence'] ?? 0,
            totalAppointments: $statsData['total_appointments'] ?? 0,
            totalServiceTime: Duration::fromMinutes($statsData['total_service_time_minutes'] ?? 0),
            atRisk: $statsData['at_risk'] ?? false,
            completionPercentage: $statsData['completion_percentage'] ?? 0,
        );
    }
}
