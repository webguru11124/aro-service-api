<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\Tracking\Entities\ServicedRoute;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\Factories\RouteStatsFactory;
use Tests\Tools\Factories\ServiceProFactory;

class ServicedRouteFactory extends AbstractFactory
{
    protected function single($overrides = []): ServicedRoute
    {
        $scheduledAppointments = $overrides['scheduledAppointments'] ?? ScheduledAppointmentFactory::many(3);
        $plannedEvents = $overrides['plannedEvents'] ?? [];

        $route = new ServicedRoute(
            id: $overrides['id'] ?? $this->faker->randomNumber(6),
            servicePro: $overrides['servicePro'] ?? ServiceProFactory::make(),
            routeStats: $overrides['routeStats'] ?? RouteStatsFactory::make(),
            geometry: $overrides['geometry'] ?? $this->faker->text(),
            trackingData: $overrides['trackingData'] ?? RouteTrackingDataFactory::make(),
            drivingStats: $overrides['drivingStats'] ?? RouteDrivingStatsFactory::make(),
        );

        foreach ($scheduledAppointments as $appointment) {
            $route->addScheduledAppointment($appointment);
        }

        foreach ($plannedEvents as $event) {
            $route->addPlannedEvent($event);
        }

        return $route;
    }
}
