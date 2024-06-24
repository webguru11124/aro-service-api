<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;

class AddExtraTimeToGetToFirstLocation extends AbstractGeneralOptimizationRule
{
    /**
     * Rule to add extra time to get to the first location
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $optimizationState->getRoutes()->each(function (Route $route) {
            $route->setTimeWindow($this->getAdjustedWorkingHours($route->getTimeWindow()));
        });

        return $this->buildSuccessExecutionResult();
    }

    private function getAdjustedWorkingHours(TimeWindow $timeWindow): TimeWindow
    {
        $extraTravelTime = $this->getAmountOfExtraTravelTimeMins();

        return new TimeWindow(
            Carbon::instance($timeWindow->getStartAt())->subMinutes($extraTravelTime),
            Carbon::instance($timeWindow->getEndAt())->addMinutes($extraTravelTime)
        );
    }

    private function getAmountOfExtraTravelTimeMins(): int
    {
        return DomainContext::getTravelTimeToFirstLocation();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Add Extra Time To Get To First Location';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        $extraTravelTime = $this->getAmountOfExtraTravelTimeMins();

        return "This rule adds $extraTravelTime Minutes of extra travel time at the start of a route to allow the service pro to reach the first location.";
    }
}
