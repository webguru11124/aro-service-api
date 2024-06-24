<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\ServicePoint;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class ServicePointFactory extends AbstractFactory
{
    public function single($overrides = []): ServicePoint
    {
        return new ServicePoint(
            $overrides['id'] ?? $this->faker->randomNumber(2),
            $overrides['referenceId'] ?? $this->faker->randomNumber(5),
            $overrides['location'] ?? new Coordinate(
                $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
                $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
            ),
            $overrides['priority'] ?? $this->faker->numberBetween(0, 100),
            $overrides['preferredEmployeeId'] ?? null,
            $overrides['reserved'] ?? false,
        );
    }
}
