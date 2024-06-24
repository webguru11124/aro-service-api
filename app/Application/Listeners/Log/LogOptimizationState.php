<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\OptimizationState\AbstractOptimizationStateEvent;
use App\Application\Events\OptimizationState\OptimizationStateStored;
use Illuminate\Support\Facades\Log;

class LogOptimizationState
{
    private AbstractOptimizationStateEvent $event;

    /**
     * Handle the event.
     */
    public function handle(AbstractOptimizationStateEvent $event): void
    {
        $this->event = $event;

        match (true) {
            $event instanceof OptimizationStateStored => $this->logStateStored(),
            default => null,
        };
    }

    private function logStateStored(): void
    {
        Log::debug(
            $this->event->optimizationState->getStatus()->name . ' Optimization State',
            [
                'office_id' => $this->event->optimizationState->getOffice()->getId(),
                'date' => $this->event->optimizationState->getDate()->toDateString(),
                'optimization_state_id' => $this->event->optimizationState->getId(),
                'is_simulation' => $this->event->optimizationState->getOptimizationParams()->simulationRun,
                'is_create_plan' => $this->event->optimizationState->getOptimizationParams()->buildPlannedOptimization,
                'is_last_optimization' => $this->event->optimizationState->getOptimizationParams()->lastOptimizationRun,
            ]
        );
    }
}
