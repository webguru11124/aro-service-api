<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class MustNotExceedMaxWorkingHours extends AbstractGeneralOptimizationRule
{
    private CarbonInterface $date;
    private bool $isOnSeason = false;

    /**
     * Rule to ensure that the total working time for a service pro does not exceed a certain amount
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $this->date = $optimizationState->getDate();
        $this->resolveSeason();

        $optimizationState->getRoutes()->each(function (Route $route) {
            $route->setTimeWindow(
                $this->getTimeWindow(
                    $route->getServicePro()->getWorkingHours()->getStartAt(),
                    $route->getRouteType(),
                )
            );
        });

        return $this->buildSuccessExecutionResult();
    }

    private function resolveSeason(): void
    {
        $this->isOnSeason = $this->date->month >= 4 && $this->date->month <= 10; // April to October
    }

    private function getTimeWindow(CarbonInterface $startAt, RouteType $routeType): TimeWindow
    {
        $totalBreaksTime = DomainContext::getWorkBreakDuration() * 2 + DomainContext::getLunchDuration();
        $totalWorkTime = DomainContext::getMaxWorkTime($routeType, $this->isOnSeason) + $totalBreaksTime;

        return new TimeWindow(
            Carbon::instance($startAt),
            Carbon::instance($startAt)->addMinutes($totalWorkTime)
        );
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Not Exceed Max Working Hours';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        $maxWorkTime = DomainContext::getMaxWorkTime(RouteType::REGULAR_ROUTE, $this->isOnSeason);
        $totalBreaksTime = DomainContext::getWorkBreakDuration() * 2 + DomainContext::getLunchDuration();

        return 'This rule ensures that the total working time for a service pro does not exceed ' . round($maxWorkTime / 60, 1) . " hours, including $totalBreaksTime minutes of breaks. Actual value depends on season.";
    }
}
