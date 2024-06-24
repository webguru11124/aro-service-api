<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

class MustStartAtServiceProHomeLocation extends AbstractGeneralOptimizationRule
{
    /**
     * Rule to ensure that the route starts at the service pro home location
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $optimizationState->getRoutes()->each(function (Route $route) {
            $route->setStartLocationCoordinatesToServiceProHome();
        });

        return $this->buildSuccessExecutionResult();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Start At Service Pro Home Location';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule ensures that the route starts at the service pro home location when optimization is performed. This ensures that the optimization engine does not start the service pro a great distance from thier home location.';
    }
}
