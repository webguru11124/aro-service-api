<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\Tracking\Entities\FleetRoute;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\Factories\RouteStatsFactory;
use Tests\Tools\Factories\ServiceProFactory;

class FleetRouteFactory extends AbstractFactory
{
    protected function single($overrides = []): FleetRoute
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(6);
        $startAt = $overrides['startAt'] ?? new Carbon($this->faker->dateTimeBetween('-1 hour', '+1 hour'));
        $servicePro = $overrides['servicePro'] ?? ServiceProFactory::make();
        $appointments = $overrides['appointments'] ?? collect([PlannedAppointmentFactory::make()]);
        $routeStats = $overrides['routeStats'] ?? RouteStatsFactory::make();
        $drivingStats = $overrides['drivingStats'] ?? RouteDrivingStatsFactory::make();
        $completionStats = $overrides['completionStats'] ?? RouteCompletionStatsFactory::make();

        $fleetRoute = new FleetRoute(
            $id,
            $startAt,
            $servicePro,
            $routeStats,
            $overrides['geometry'] ?? $this->faker->text(),
        );

        $fleetRoute->setRoute($appointments);
        $fleetRoute->setDrivingStats($drivingStats);
        $fleetRoute->setCompletionStats($completionStats);

        return $fleetRoute;
    }
}
