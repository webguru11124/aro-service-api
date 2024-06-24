<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

interface WorkEvent
{
    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return Duration
     */
    public function getDuration(): Duration;

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType;

    /**
     * @return TimeWindow|null
     */
    public function getTimeWindow(): TimeWindow|null;
}
