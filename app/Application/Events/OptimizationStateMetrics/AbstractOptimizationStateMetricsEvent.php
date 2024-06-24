<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationStateMetrics;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractOptimizationStateMetricsEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly OptimizationState $optimizationState,
        public readonly \Throwable|null $exception = null
    ) {
    }

    abstract public function getDescription(): string;
}
