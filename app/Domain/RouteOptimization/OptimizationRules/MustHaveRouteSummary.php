<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

class MustHaveRouteSummary extends AbstractGeneralOptimizationRule
{
    /**
     * Rule to add route summary to the last spot
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $optimizationState->getRoutes()->each(
            fn (Route $route) => $route->enableRouteSummary()
        );

        return $this->buildSuccessExecutionResult();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Route Summary';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule adds route summary to the last spot.';
    }
}
