<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\RouteOptimization\Entities\OptimizationState;

interface PostOptimizationHandler
{
    /**
     * Applies the business rule after the optimization process
     *
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    public function process(OptimizationState $optimizationState): void;
}
