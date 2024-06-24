<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationStateMetrics;

class CollectOptimizationStateMetricsEnded extends AbstractOptimizationStateMetricsEvent
{
    public function getDescription(): string
    {
        return 'COMPLETE - Collect Optimization State Metrics';
    }
}
