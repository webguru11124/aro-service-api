<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;

class ReservedTimeFactory extends AbstractFactory
{
    /**
     * @param array $overrides
     *
     * @return ReservedTime
     * @throws InvalidTimeWindowException
     */
    public function single($overrides = []): ReservedTime
    {
        return (new ReservedTime(
            $overrides['id'] ?? $this->faker->randomNumber(6),
            $overrides['description'] ?? 'Not Working'
        ))
            ->setDuration($overrides['duration'] ?? Duration::fromMinutes(15))
            ->setTimeWindow($overrides['timeWindow'] ?? new TimeWindow(
                Carbon::tomorrow()->hour(9),
                Carbon::tomorrow()->hour(9)->addMinutes(15)
            ))
            ->setExpectedArrival($overrides['expectedArrival'] ?? new TimeWindow(
                Carbon::tomorrow()->hour(9),
                Carbon::tomorrow()->hour(9)->addMinutes(15)
            ))
            ->setRouteId($overrides['routeId'] ?? 100);
    }
}
