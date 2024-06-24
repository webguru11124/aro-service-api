<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use Carbon\CarbonInterface;

interface PostOptimizationRuleCaster
{
    /**
     * Processes the rule
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     * @param PostOptimizationRule $rule
     *
     * @return RuleExecutionResult
     */
    public function process(
        CarbonInterface $date,
        OptimizationState $optimizationState,
        PostOptimizationRule $rule
    ): RuleExecutionResult;
}
