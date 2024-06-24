<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

class MustConsiderRoadTraffic extends AbstractGeneralOptimizationRule
{
    /**
     * Enables route traffic consideration
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        // TODO: Rule is disabled until we have a proper traffic data and engine to process it
        // $optimizationState->enableRouteTrafficConsideration();

        return $this->buildTriggeredExecutionResult();
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Consider traffic estimation in optimization solution';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Consider Road Traffic';
    }
}
