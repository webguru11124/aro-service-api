<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Vroom;

use App\Infrastructure\Services\Vroom\DTO\Capacity;
use App\Infrastructure\Services\Vroom\DTO\Skills;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use App\Infrastructure\Services\Vroom\DTO\VroomCoordinate;
use App\Infrastructure\Services\Vroom\DTO\VroomTimeWindow;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class VehicleFactory extends AbstractFactory
{
    protected function single($overrides = []): mixed
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(5);
        $description = $overrides['description'] ?? $this->faker->name();
        $skills = $overrides['skills'] ?? new Skills([
            $this->faker->randomNumber(4),
            $this->faker->randomNumber(4),
        ]);

        $startLocation = $overrides['startLocation'] ?? new VroomCoordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );
        $endLocation = $overrides['endLocation'] ?? new VroomCoordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );
        $timeWindow = $overrides['timeWindow'] ?? new VroomTimeWindow(
            Carbon::now(),
            Carbon::now()->addHours(1),
        );
        $capacity = $overrides['capacity'] ?? new Capacity([$this->faker->randomNumber(2)]);
        $speedFactor = $overrides['speed_factor'] ?? $this->faker->randomFloat(1, 1, 2);

        return new Vehicle(
            $id,
            $description,
            $skills,
            $startLocation,
            $endLocation,
            $timeWindow,
            $capacity,
            $speedFactor
        );
    }
}
