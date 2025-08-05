<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;

class RestrictTimeWindow extends AbstractGeneralOptimizationRule
{
    private const START_HOUR = 8;
    private const END_HOUR = 18;
    private const FEATURE_FLAG = 'isRestrictServiceProTimeAvailabilityEnabled';

    public function __construct(
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Restricts route time windows within OptimizationState to specified local hours.
     * Note: When this rule is active, it prevents the application of the ExtendWorkingTime rule,
     * ensuring that route time windows are not extended beyond the restricted hours.
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        if (!$this->isTimeWindowAppointmentsFeatureEnabled($optimizationState->getOffice()->getId())) {
            return $this->buildTriggeredExecutionResult();
        }

        $optimizationState->getRoutes()->each(function (Route $route) {
            $this->applyTimeWindowRestriction($route);
        });

        return $this->buildSuccessExecutionResult();
    }

    private function applyTimeWindowRestriction(Route $route): void
    {
        $currentTimeWindow = $route->getTimeWindow();
        $restrictedTimeWindow = $this->getRestrictedTimeWindow(
            $currentTimeWindow->getStartAt(),
            $currentTimeWindow->getEndAt()
        );
        $route->setTimeWindow($restrictedTimeWindow);
    }

    private function getRestrictedTimeWindow(CarbonInterface $startAt, CarbonInterface $endAt): TimeWindow
    {
        $startHour = max($startAt->hour, self::START_HOUR);
        $endHour = min($endAt->hour, self::END_HOUR);

        $restrictedStartAt = $startAt->copy()->setTime($startHour, 0, 0);
        $restrictedEndAt = $endAt->copy()->setTime($endHour, 0, 0);

        return new TimeWindow($restrictedStartAt, $restrictedEndAt);
    }

    private function isTimeWindowAppointmentsFeatureEnabled(int $officeId): bool
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
        return 'Restrict Time Window';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule restricts the time windows on a route to the specified local hours. When this rule is active time windows on a route cannot be set before 0' . self::START_HOUR . ':00 and cannot be set after ' . self::END_HOUR . ":00. Note that when this rule is active, it prevents the application of the Extend Working Time rule, ensuring that route time windows are not extended beyond the restricted hours. Also not that this rule is only active when the 'isRestrictServiceProTimeAvailabilityEnabled' feature flag is enabled. You will need to check if that feature flag was enabled to see if this rule was active.";
    }
}
