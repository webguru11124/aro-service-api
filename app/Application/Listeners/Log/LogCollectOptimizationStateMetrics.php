<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\OptimizationStateMetrics\AbstractOptimizationStateMetricsEvent;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsEnded;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsFailed;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsStarted;
use Illuminate\Support\Facades\Log;

class LogCollectOptimizationStateMetrics
{
    private AbstractOptimizationStateMetricsEvent $event;

    /**
     * Handle the event.
     */
    public function handle(AbstractOptimizationStateMetricsEvent $event): void
    {
        $this->event = $event;

        match (true) {
            $event instanceof CollectOptimizationStateMetricsStarted => $this->log(),
            $event instanceof CollectOptimizationStateMetricsEnded => $this->log(),
            $event instanceof CollectOptimizationStateMetricsFailed => $this->logFailed(),
            default => null,
        };
    }

    private function log(): void
    {
        Log::info($this->event->getDescription(), [
            'optimization_state_id' => $this->event->optimizationState->getId(),
            'status' => $this->event->optimizationState->getStatus()->value,
            'office_id' => $this->event->optimizationState->getOffice()->getId(),
        ]);
    }

    private function logFailed(): void
    {
        Log::error($this->event->getDescription(), [
            'optimization_state_id' => $this->event->optimizationState->getId(),
            'status' => $this->event->optimizationState->getStatus()->value,
            'office_id' => $this->event->optimizationState->getOffice()->getId(),
            'exception' => $this->event->exception,
        ]);
    }
}
