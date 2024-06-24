<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

class MustEndAtServiceProHomeLocation extends AbstractGeneralOptimizationRule
{
    /**
     * Rule to ensure that the route ends at the service pro home location
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $optimizationState->getRoutes()->each(function (Route $route) {
            $route->setEndLocationCoordinatesToServiceProHome();
        });

        return $this->buildSuccessExecutionResult();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must End At Service Pro Home Location';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule ensures that the route ends at the service pro home location.';
    }
}
