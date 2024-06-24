<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities\Events;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

interface FleetRouteEvent
{
    /**
     * Returns ID of the event
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Returns time window of the event
     *
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow;

    /**
     * Returns location of the event
     *
     * @return Coordinate
     */
    public function getLocation(): Coordinate;
}
