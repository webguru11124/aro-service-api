<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\OptimizationRule;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

interface GeneralOptimizationRule extends OptimizationRule
{
    /**
     * Updates OptimizationState according to business rule
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult;
}
