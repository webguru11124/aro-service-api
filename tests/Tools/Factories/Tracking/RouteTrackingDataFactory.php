<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;

class RouteTrackingDataFactory extends AbstractFactory
{
    public function single($overrides = []): RouteTrackingData
    {
        return new RouteTrackingData(
            $overrides['id'] ?? $this->faker->uuid(),
            $overrides['driverLocation'] ?? new Coordinate(
                $this->faker->latitude,
                $this->faker->longitude
            ),
            $overrides['driverLocatedAt'] ?? Carbon::now(),
            $overrides['vehicleLocation'] ?? new Coordinate(
                $this->faker->latitude,
                $this->faker->longitude
            ),
            $overrides['vehicleLocatedAt'] ?? Carbon::now(),
            $overrides['vehicleSpeed'] ?? $this->faker->randomFloat(2, 0, 100),
        );
    }
}
