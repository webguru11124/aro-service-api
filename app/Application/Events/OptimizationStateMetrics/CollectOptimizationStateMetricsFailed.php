<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationStateMetrics;

class CollectOptimizationStateMetricsFailed extends AbstractOptimizationStateMetricsEvent
{
    public function getDescription(): string
    {
        return 'FAILED - Collect Optimization State Metrics';
    }
}
