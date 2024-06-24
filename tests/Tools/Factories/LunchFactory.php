<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Tests\Tools\TestValue;

class LunchFactory extends AbstractFactory
{
    public const DURATION = 30;
    private const START_AT = 10;

    public function single($overrides = []): Lunch
    {
        $routeId = $overrides['routeId'] ?? TestValue::ROUTE_ID;
        $timeWindow = $overrides['timeWindow'] ?? new TimeWindow(
            Carbon::tomorrow()->hour(self::START_AT),
            Carbon::tomorrow()->hour(self::START_AT)->addMinutes(self::DURATION)
        );

        $lunch = new Lunch(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['description'] ?? 'Lunch Break',
        );

        $expectedArrival = $overrides['expectedArrival'] ?? $timeWindow;

        $lunch
            ->setDuration(new Duration(CarbonInterval::minutes($overrides['duration'] ?? self::DURATION)))
            ->setRouteId($routeId)
            ->setTimeWindow($timeWindow)
            ->setExpectedArrival($expectedArrival);

        return $lunch;
    }
}
