<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\RouteOptimization;

use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class MeetingFactory extends AbstractFactory
{
    public function single($overrides = []): Meeting
    {
        $startAt = Carbon::today()->hour(TestValue::START_OF_DAY);
        $timeWindow = $overrides['timeWindow'] ?? new TimeWindow(
            $startAt,
            $startAt->clone()->addHour(),
        );
        $location = $overrides['location'] ?? new Coordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );

        return new Meeting(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['description'] ?? $this->faker->text(),
            $timeWindow,
            $location,
        );
    }
}
