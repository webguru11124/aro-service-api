<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Vroom;

use App\Infrastructure\Services\Vroom\DTO\Delivery;
use App\Infrastructure\Services\Vroom\DTO\Job;
use App\Infrastructure\Services\Vroom\DTO\Skills;
use App\Infrastructure\Services\Vroom\DTO\VroomCoordinate;
use App\Infrastructure\Services\Vroom\DTO\VroomTimeWindow;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class JobFactory extends AbstractFactory
{
    private const DESCRIPTION = 'Basic';
    private const SERVICE_DURATION = 1200;
    private const SETUP_DURATION = 180;
    private const PRIORITY = 20;

    protected function single($overrides = []): mixed
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(5);
        $description = $overrides['description'] ?? self::DESCRIPTION;
        $skills = $overrides['skills'] ?? new Skills([
            $this->faker->randomNumber(4),
            $this->faker->randomNumber(4),
        ]);

        $location = $overrides['endLocation'] ?? new VroomCoordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );

        $service = $overrides['service'] ?? self::SERVICE_DURATION;
        $delivery = $overrides['delivery'] ?? new Delivery([1]);
        $priority = $overrides['priority'] ?? self::PRIORITY;
        $setup = $overrides['setup'] ?? self::SETUP_DURATION;
        $timeWindow = $overrides['setup'] ?? new VroomTimeWindow(
            Carbon::tomorrow()->startOfDay(),
            Carbon::tomorrow()->midDay(),
        );

        return new Job(
            $id,
            $description,
            $skills,
            $service,
            $delivery,
            $location,
            $priority,
            $setup,
            $timeWindow
        );
    }
}
