<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

readonly class Waiting implements WorkEvent
{
    public function __construct(
        private TimeWindow $timeWindow,
    ) {
    }

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::WAITING;
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return $this->timeWindow;
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        $seconds = $this->getTimeWindow()->getTotalSeconds();

        return Duration::fromSeconds($seconds);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getType()->value;
    }
}
