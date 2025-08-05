<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\RouteOptimization\Entities\OptimizationState;

interface OptimizationStatePersister
{
    /**
     * Persists the optimization state
     *
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    public function persist(OptimizationState $optimizationState): void;
}
