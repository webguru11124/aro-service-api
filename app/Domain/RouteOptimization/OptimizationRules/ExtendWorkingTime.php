<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;

class ExtendWorkingTime extends AbstractAdditionalOptimizationRule
{
    private const TIME_WINDOW_EXTEND_MINUTES = 30;
    private const FEATURE_FLAG = 'isRestrictServiceProTimeAvailabilityEnabled';

    private CarbonInterface $date;
    private bool $isOnSeason = false;

    public function __construct(
        private readonly FeatureFlagService $featureFlagService
    ) {
    }

    /**
     * Extends route time windows within OptimizationState by specified minutes.
     * This rule is applied only if the RestrictTimeWindow rule is not enabled.
     *
     * @param OptimizationState $sourceOptimizationState
     * @param OptimizationState $resultOptimizationState
     *
     * @return void
     */
    public function process(OptimizationState $sourceOptimizationState, OptimizationState $resultOptimizationState): void
    {
        if ($this->isSkipped($sourceOptimizationState)) {
            return;
        }

        $this->date = $sourceOptimizationState->getDate();
        $this->resolveSeason();

        if (!$this->isRestrictTimeWindowEnabled($sourceOptimizationState->getOffice()->getId())) {
            $sourceOptimizationState->getRoutes()->each(function (Route $route) {
                $this->extendWorkingTimeWindow($route);
            });
            $sourceOptimizationState->addRuleExecutionResults(collect([
                $this->buildSuccessExecutionResult(),
            ]));

            return;
        }

        $sourceOptimizationState->addRuleExecutionResults(collect([
            $this->buildTriggeredExecutionResult(),
        ]));
    }

    private function resolveSeason(): void
    {
        $this->isOnSeason = $this->date->month >= 4 && $this->date->month <= 10; // April to October
    }

    private function extendWorkingTimeWindow(Route $route): void
    {
        $currentTimeWindow = $route->getTimeWindow();
        $extendedTimeWindow = $this->getExtendedTimeWindow(
            $currentTimeWindow->getStartAt(),
            $currentTimeWindow->getEndAt(),
            $route->getRouteType(),
        );
        $route->setTimeWindow($extendedTimeWindow);
    }

    private function getExtendedTimeWindow(CarbonInterface $startAt, CarbonInterface $endAt, RouteType $routeType): TimeWindow
    {
        $extendedTimeWindow = new TimeWindow(
            $startAt,
            $endAt->clone()->addMinutes(self::TIME_WINDOW_EXTEND_MINUTES)
        );

        $maxWorkingTime = DomainContext::getMaxWorkTime($routeType, $this->isOnSeason);

        if ($extendedTimeWindow->getTotalMinutes() > $maxWorkingTime) {
            $extendedTimeWindow = new TimeWindow(
                $startAt,
                $startAt->clone()->addMinutes($maxWorkingTime)
            );
        }

        return $extendedTimeWindow;
    }

    private function isRestrictTimeWindowEnabled(int $officeId): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $officeId,
            self::FEATURE_FLAG
        );
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Extend Working Time';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule extends the working time of a route by ' . self::TIME_WINDOW_EXTEND_MINUTES . ' minutes. This rule is applied only if the Restrict Time Window rule is not enabled.';
    }
}
