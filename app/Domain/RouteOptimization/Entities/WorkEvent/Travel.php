<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class Travel implements WorkEvent
{
    private null|int $routeId = null;

    public function __construct(
        private readonly Distance $distance,
        private readonly TimeWindow $timeWindow,
        private readonly null|int $id = null,
    ) {
    }

    public function __clone()
    {
        if (isset($this->distance)) {
            /** @phpstan-ignore-next-line */
            $this->distance = clone $this->distance;
        }

        if (isset($this->timeWindow)) {
            /** @phpstan-ignore-next-line */
            $this->timeWindow = clone $this->timeWindow;
        }
    }

    public function getDescription(): string
    {
        return 'Travel';
    }

    public function getDuration(): Duration
    {
        $interval = $this->timeWindow->getTotalSeconds();

        return Duration::fromSeconds($interval);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getRouteId(): int|null
    {
        return $this->routeId;
    }

    /**
     * @param int $routeId
     *
     * @return void
     */
    public function setRouteId(int $routeId): void
    {
        $this->routeId = $routeId;
    }

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::TRAVEL;
    }

    /**
     * @return Distance
     */
    public function getDistance(): Distance
    {
        return $this->distance;
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return $this->timeWindow;
    }
}
