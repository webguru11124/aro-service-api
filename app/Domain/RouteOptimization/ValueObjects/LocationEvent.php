<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;

abstract readonly class LocationEvent implements WorkEvent
{
    public function __construct(
        protected CarbonInterface $startAt,
        protected Coordinate $location,
    ) {
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return new Duration(CarbonInterval::seconds(0));
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return new TimeWindow($this->startAt, $this->startAt);
    }
}
