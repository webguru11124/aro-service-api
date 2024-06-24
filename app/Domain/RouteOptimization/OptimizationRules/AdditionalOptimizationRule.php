<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\OptimizationRule;
use App\Domain\RouteOptimization\Entities\OptimizationState;

interface AdditionalOptimizationRule extends OptimizationRule
{
    /**
     * Updates OptimizationState according to business rule
     *
     * @param OptimizationState $sourceOptimizationState
     * @param OptimizationState $resultOptimizationState
     *
     * @return void
     */
    public function process(OptimizationState $sourceOptimizationState, OptimizationState $resultOptimizationState): void;
}
