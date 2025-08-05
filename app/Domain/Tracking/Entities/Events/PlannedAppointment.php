<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities\Events;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

readonly class PlannedAppointment implements FleetRouteEvent
{
    /**
     * @param int $id
     * @param TimeWindow $timeWindow
     * @param Coordinate $location
     */
    public function __construct(
        private int $id,
        private TimeWindow $timeWindow,
        private Coordinate $location,
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return $this->timeWindow;
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }
}
