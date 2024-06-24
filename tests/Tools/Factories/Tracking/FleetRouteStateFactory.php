<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\ValueObjects\OptimizationStateMetrics;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class FleetRouteStateFactory extends AbstractFactory
{
    public function single($overrides = []): FleetRouteState
    {
        $fleetRouteState = new FleetRouteState(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['officeId'] ?? $this->faker->randomNumber(6),
            $overrides['date'] ?? Carbon::today(),
            $overrides['updatedAt'] ?? null,
            $overrides['metrics'] ?? new OptimizationStateMetrics(
                optimizationScore: 100,
            ),
        );

        $fleetRoutes = $overrides['fleetRoutes'] ?? FleetRouteFactory::many(2);

        foreach ($fleetRoutes as $fleetRoute) {
            $fleetRouteState->addFleetRoute($fleetRoute);
        }

        return $fleetRouteState;
    }
}
