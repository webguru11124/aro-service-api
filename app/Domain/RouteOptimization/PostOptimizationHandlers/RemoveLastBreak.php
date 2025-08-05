<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;

class RemoveLastBreak implements PostOptimizationHandler
{
    public function process(OptimizationState $optimizationState): void
    {
        foreach ($optimizationState->getRoutes() as $route) {
            $this->processRoute($route);
        }
    }

    private function processRoute(Route $route): void
    {
        $filteredEvents = $route->getWorkEvents()->filter(
            fn (WorkEvent $workEvent) => $workEvent instanceof Appointment
                || $workEvent instanceof WorkBreak
        );

        $lastEvent = $filteredEvents->last();

        if ($lastEvent instanceof WorkBreak) {
            $route->removeWorkEvent($lastEvent);

            $this->processRoute($route);
        }
    }
}
