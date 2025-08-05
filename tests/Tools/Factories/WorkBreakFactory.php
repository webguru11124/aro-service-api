<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class WorkBreakFactory extends AbstractFactory
{
    public function single($overrides = []): WorkBreak
    {
        $routeId = $overrides['routeId'] ?? TestValue::ROUTE_ID;
        $timeWindow = $overrides['timeWindow'] ?? new TimeWindow(
            Carbon::tomorrow()->hour(9),
            Carbon::tomorrow()->hour(9)->addMinutes(TestValue::WORK_BREAK_DURATION)
        );

        $expectedArrival = $overrides['expectedArrival'] ?? $timeWindow;
        $duration = $overrides['duration'] ?? Duration::fromMinutes(TestValue::WORK_BREAK_DURATION);

        return (new WorkBreak(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['description'] ?? '15 Min Break',
        ))
            ->setDuration($duration)
            ->setTimeWindow($timeWindow)
            ->setExpectedArrival($expectedArrival)
            ->setRouteId($routeId);
    }
}
