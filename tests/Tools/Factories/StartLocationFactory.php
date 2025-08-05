<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class StartLocationFactory extends AbstractFactory
{
    public function single($overrides = []): StartLocation
    {
        return new StartLocation(
            $overrides['startAt'] ?? Carbon::tomorrow()->setTimeFromTimeString('08:00:00'),
            $overrides['location'] ?? new Coordinate(
                $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
                $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
            )
        );
    }
}
