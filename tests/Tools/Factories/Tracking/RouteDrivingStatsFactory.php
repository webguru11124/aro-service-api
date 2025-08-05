<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use Tests\Tools\Factories\AbstractFactory;

class RouteDrivingStatsFactory extends AbstractFactory
{
    protected function single($overrides = []): mixed
    {
        return new RouteDrivingStats(
            id: $overrides['id'] ?? $this->faker->uuid(),
            totalDriveTime: $driveTime = Duration::fromMinutes($overrides['totalDriveTime'] ?? $this->faker->randomNumber(3)),
            totalDriveDistance: Distance::fromMeters($overrides['totalDriveDistance'] ?? $this->faker->randomNumber(2)),
            averageDriveTimeBetweenServices: Duration::fromMinutes($overrides['averageDriveTimeBetweenServices'] ?? $this->faker->randomNumber(2)),
            averageDriveDistanceBetweenServices: Distance::fromMiles($overrides['averageDriveDistanceBetweenServices'] ?? $this->faker->randomNumber(1)),
            totalWorkingTime: $overrides['totalWorkingTime'] ?? $driveTime->increase(Duration::fromMinutes($this->faker->randomNumber(2))),
            fuelConsumption: $overrides['fuelConsumption'] ?? $this->faker->randomNumber(2),
            historicVehicleMileage: Distance::fromMiles($overrides['historicVehicleMileage'] ?? $this->faker->randomNumber(2)),
            historicFuelConsumption: $overrides['historicFuelConsumption'] ?? $this->faker->randomNumber(2),
        );
    }
}
