<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\Factories\ServiceProFactory;

class ScheduledRouteFactory extends AbstractFactory
{
    public function single($overrides = []): ScheduledRoute
    {
        $scheduledRoute = new ScheduledRoute(
            id: $overrides['id'] ?? $this->faker->randomNumber(6),
            officeId: $overrides['officeId'] ?? $this->faker->randomNumber(2),
            date: $overrides['date'] ?? Carbon::today(),
            servicePro: $overrides['servicePro'] ?? ServiceProFactory::make(),
            routeType: $overrides['routeType'] ?? RouteType::REGULAR_ROUTE,
            actualCapacityCount: $overrides['actualCapacityCount'] ?? 20,
        );

        $appointments = $overrides['appointments'] ?? AppointmentFactory::many(5);
        foreach ($appointments as $appointment) {
            $scheduledRoute->addAppointment($appointment);
        }

        $pendingServices = $overrides['pendingServices'] ?? PendingServiceFactory::many(2);
        foreach ($pendingServices as $pendingService) {
            $scheduledRoute->addPendingService($pendingService);
        }

        return $scheduledRoute;
    }
}
