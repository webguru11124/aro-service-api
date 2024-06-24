<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;

class AddExtraWorkEvents implements PostOptimizationHandler
{
    private const FEATURE_FLAG = 'isAddExtraWorkEventsEnabled';
    private const MIN_EXTRA_WORK_EVENT_DURATION = 30;

    public function __construct(
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Adds ExtraWork events to routes in the given OptimizationState based on the duration of Travel events and feature flag status.
     *
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    public function process(OptimizationState $optimizationState): void
    {
        if ($this->isExtraWorkEventsFeatureEnabled($optimizationState->getOffice()->getId())) {
            $optimizationState->getRoutes()->each(function (Route $route) {
                $this->processRoute($route);
            });
        }
    }

    private function processRoute(Route $route): void
    {
        $allTravelEvents = $route->getTravelEvents();
        $allWaitingEvents = $route->getWaitingEvents();

        foreach ($allTravelEvents as $travel) {
            if ($travel->getTimeWindow()->getTotalMinutes() >= self::MIN_EXTRA_WORK_EVENT_DURATION) {
                $route->addExtraWorkByTravelOrWaiting($travel);
            }
        }

        foreach ($allWaitingEvents as $waiting) {
            if ($waiting->getDuration()->getTotalMinutes() >= self::MIN_EXTRA_WORK_EVENT_DURATION) {
                $route->addExtraWorkByTravelOrWaiting($waiting);
            }
        }
    }

    private function isExtraWorkEventsFeatureEnabled(int $officeId): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $officeId,
            self::FEATURE_FLAG
        );
    }
}
