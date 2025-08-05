<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\ValueObjects;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Illuminate\Support\Collection;

class RouteCapacity
{
    public function __construct(
        private readonly RouteType $routeType,
        private readonly int $actualCapacityCount,
        private readonly Collection $eventsDuration,
    ) {
    }

    /**
     * Returns the available capacity
     *
     * @return int
     */
    public function getValue(): int
    {
        $capacity = $this->getAvailableCapacity();

        if ($this->eventsDuration->isEmpty()) {
            return $capacity;
        }

        $events = $this->eventsDuration->reduce(
            function (int $carry, Duration $eventDuration) {
                return $carry + (int) ceil($eventDuration->getTotalMinutes() / DomainContext::getSpotDuration());
            },
            0
        );

        return max($capacity - $events, 0);
    }

    private function getAvailableCapacity(): int
    {
        return $this->getMaxAvailableCapacityCount() ?: DomainContext::getMaxAllowedAppointmentsPerDay($this->routeType);
    }

    private function getMaxAvailableCapacityCount(): int
    {
        $reservedSpots = DomainContext::getReservedSpotsForBlockedReasons($this->routeType);

        if ($this->actualCapacityCount > $reservedSpots) {
            return $this->actualCapacityCount - $reservedSpots;
        }

        return $this->actualCapacityCount;
    }
}
