<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\ClusterOfServices;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class ClusterOfServicesFactory extends AbstractFactory
{
    public function single($overrides = []): ClusterOfServices
    {
        $centroid = $overrides['centroid'] ?? new Coordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );

        $cluster = new ClusterOfServices(
            $overrides['id'] ?? $this->faker->randomNumber(),
            $overrides['capacity'] ?? $this->faker->randomNumber(2),
            $centroid,
            $overrides['employeeId'] ?? null,
        );

        $servicePoints = $overrides['servicePoints'] ?? ServicePointFactory::many(5);

        foreach ($servicePoints as $servicePoint) {
            $cluster->addService($servicePoint);
        }

        return $cluster;
    }
}
