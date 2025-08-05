<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use Tests\Tools\Factories\AbstractFactory;

class RouteCompletionStatsFactory extends AbstractFactory
{
    protected function single($overrides = []): mixed
    {
        return new RouteCompletionStats(
            routeAdherence: $overrides['routeAdherence'] ?? $this->faker->randomNumber(2),
            totalAppointments: $overrides['totalAppointments'] ?? $this->faker->randomNumber(1),
            totalServiceTime: Duration::fromMinutes($overrides['totalServiceTime'] ?? $this->faker->randomNumber(3)),
            atRisk: $overrides['atRisk'] ?? $this->faker->boolean(),
            completionPercentage: $overrides['completionPercentage'] ?? $this->faker->randomNumber(2),
        );
    }
}
