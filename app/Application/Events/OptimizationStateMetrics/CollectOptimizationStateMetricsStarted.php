<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationStateMetrics;

class CollectOptimizationStateMetricsStarted extends AbstractOptimizationStateMetricsEvent
{
    public function getDescription(): string
    {
        return 'STARTED - Collect Optimization State Metrics';
    }
}
