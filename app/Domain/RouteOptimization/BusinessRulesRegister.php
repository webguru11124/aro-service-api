<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization;

use App\Domain\RouteOptimization\OptimizationRules\AddExtraTimeToGetToFirstLocation;
use App\Domain\RouteOptimization\OptimizationRules\AdditionalOptimizationRule;
use App\Domain\RouteOptimization\OptimizationRules\MustConsiderRoadTraffic;
use App\Domain\RouteOptimization\OptimizationRules\ExtendWorkingTime;
use App\Domain\RouteOptimization\OptimizationRules\IncreaseRouteCapacity;
use App\Domain\RouteOptimization\OptimizationRules\LockFirstAppointment;
use App\Domain\RouteOptimization\OptimizationRules\MustEndAtServiceProHomeLocation;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveBalancedWorkload;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveInsideSales;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveRouteSummary;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveWorkBreaks;
use App\Domain\RouteOptimization\OptimizationRules\MustNotExceedMaxWorkingHours;
use App\Domain\RouteOptimization\OptimizationRules\GeneralOptimizationRule;
use App\Domain\RouteOptimization\OptimizationRules\VisitCalendarEventLocation;
use App\Domain\RouteOptimization\OptimizationRules\MustStartAtServiceProHomeLocation;
use App\Domain\RouteOptimization\OptimizationRules\RestrictTimeWindow;
use App\Domain\RouteOptimization\OptimizationRules\SetPreferredServicePro;
use App\Domain\RouteOptimization\OptimizationRules\SetServiceDurationToAverage;
use App\Domain\RouteOptimization\OptimizationRules\SetServiceDurationWithPredictiveModel;
use App\Domain\RouteOptimization\OptimizationRules\ShiftLockedAppointmentsTimeWindow;
use Illuminate\Support\Collection;

class BusinessRulesRegister
{
    // Main business rules that are going to be applied to OptimizationState
    private const GENERAL_OPTIMIZATION_RULES = [
        MustConsiderRoadTraffic::class,
        MustNotExceedMaxWorkingHours::class,
        MustStartAtServiceProHomeLocation::class,
        MustEndAtServiceProHomeLocation::class,
        AddExtraTimeToGetToFirstLocation::class,
        RestrictTimeWindow::class,
        VisitCalendarEventLocation::class,
        MustHaveRouteSummary::class,
        MustHaveInsideSales::class,
        MustHaveBalancedWorkload::class,
        MustHaveWorkBreaks::class,
        SetServiceDurationToAverage::class,
        SetServiceDurationWithPredictiveModel::class,
        LockFirstAppointment::class,
        SetPreferredServicePro::class,
    ];

    // Additional rules that are going to be applied to OptimizationState on 2nd and 3rd optimization attempts
    private const ADDITIONAL_OPTIMIZATION_RULES = [
        IncreaseRouteCapacity::class,
        ExtendWorkingTime::class,
        ShiftLockedAppointmentsTimeWindow::class,
    ];

    // Rules that are applied to OptimizationState on planning
    private const GENERAL_PLAN_RULES = [
        MustConsiderRoadTraffic::class,
        MustStartAtServiceProHomeLocation::class,
        MustEndAtServiceProHomeLocation::class,
        SetServiceDurationToAverage::class,
    ];

    /**
     * @return Collection<GeneralOptimizationRule>
     */
    public function getGeneralOptimizationRules(): Collection
    {
        return $this->buildRuleInstances(self::GENERAL_OPTIMIZATION_RULES);
    }

    /**
     * @return Collection<AdditionalOptimizationRule>
     */
    public function getAdditionalOptimizationRules(): Collection
    {
        return $this->buildRuleInstances(self::ADDITIONAL_OPTIMIZATION_RULES);
    }

    /**
     * @param array<class-string> $rules
     *
     * @return Collection
     */
    private function buildRuleInstances(array $rules): Collection
    {
        return new Collection(
            array_map(fn (string $ruleClass) => app($ruleClass), $rules)
        );
    }

    /**
     * @return Collection<GeneralOptimizationRule>
     */
    public function getGeneralPlanRules(): Collection
    {
        return $this->buildRuleInstances(self::GENERAL_PLAN_RULES);
    }
}
