<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;

class RouteStatsFactory extends AbstractFactory
{
    public function single($overrides = []): RouteStats
    {
        return new RouteStats(
            $overrides['totalInitials'] ?? $this->faker->randomNumber(1),
            $overrides['totalReservice'] ?? $this->faker->randomNumber(1),
            $overrides['totalRegular'] ?? $this->faker->randomNumber(1),
            $overrides['totalAppointments'] ?? $this->faker->randomNumber(2),
            $overrides['totalWeightedServices'] ?? $this->faker->randomNumber(2),
            Duration::fromMinutes($overrides['totalServiceTime'] ?? $this->faker->randomNumber(3)),
            Duration::fromMinutes($overrides['totalWorkingTime'] ?? $this->faker->randomNumber(3)),
            Duration::fromMinutes($overrides['totalBreakTime'] ?? $this->faker->randomNumber(3)),
            Duration::fromMinutes($overrides['totalDriveTime'] ?? $this->faker->randomNumber(3)),
            Distance::fromMeters($overrides['totalDriveDistance'] ?? $this->faker->randomNumber(4)),
            Duration::fromMinutes($overrides['averageDriveTimeBetweenServices'] ?? $this->faker->randomNumber(3)),
            Distance::fromMeters($overrides['averageDriveDistanceBetweenServices'] ?? $this->faker->randomNumber(4)),
            Duration::fromMinutes($overrides['fullDriveTime'] ?? $this->faker->randomNumber(3)),
            Distance::fromMeters($overrides['fullDriveDistance'] ?? $this->faker->randomNumber(4)),
        );
    }
}
