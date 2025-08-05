<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes;

use App\Application\Events\OptimizationRuleApplied;
use App\Domain\Contracts\PostOptimizationRuleCaster;
use App\Domain\Contracts\Services\OptimizationPostProcessService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\PostOptimizationRules\MustHaveBlockedInsideSales;
use App\Domain\RouteOptimization\PostOptimizationRules\MustUpdateRouteSummary;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\PostOptimizationRules\SetAppointmentEstimatedDuration;
use App\Domain\RouteOptimization\PostOptimizationRules\SetExpectServiceTimeWindow;
use App\Domain\RouteOptimization\PostOptimizationRules\SetStaticTimeWindows;
use App\Domain\RouteOptimization\PostOptimizationRules\DetectRescheduledConfirmedAppointments;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesAppointmentEstimatedDurationCaster;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesBlockedInsideSalesCaster;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesExpectServiceTimeWindowCaster;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesMustUpdateRouteSummaryCaster;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesStaticTimeWindowsCaster;
use Carbon\CarbonInterface;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\DetectRescheduledConfirmedAppointmentsCaster;

class PestRoutesOptimizationPostProcessService implements OptimizationPostProcessService
{
    public function __construct(
        /** @var array<PostOptimizationRule> $postProcessRules */
        private readonly array $postProcessRules
    ) {
    }

    /**
     * Runs post optimization actions
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    public function execute(CarbonInterface $date, OptimizationState $optimizationState): void
    {
        /** @var PostOptimizationRule $rule */
        foreach ($this->postProcessRules as $rule) {
            $caster = $this->matchCaster($rule);

            if ($caster === null) {
                continue;
            }

            if ($optimizationState->getOptimizationParams()->isRuleDisabled($rule->id())) {
                continue;
            }

            $result = $caster->process($date, $optimizationState, $rule);
            $optimizationState->addRuleExecutionResults(collect([$result]));
            OptimizationRuleApplied::dispatch($rule);
        }
    }

    private function matchCaster(PostOptimizationRule $rule): PostOptimizationRuleCaster|null
    {
        return match (get_class($rule)) {
            MustHaveBlockedInsideSales::class => app(PestRoutesBlockedInsideSalesCaster::class),
            MustUpdateRouteSummary::class => app(PestRoutesMustUpdateRouteSummaryCaster::class),
            SetExpectServiceTimeWindow::class => app(PestRoutesExpectServiceTimeWindowCaster::class),
            SetStaticTimeWindows::class => app(PestRoutesStaticTimeWindowsCaster::class),
            SetAppointmentEstimatedDuration::class => app(PestRoutesAppointmentEstimatedDurationCaster::class),
            DetectRescheduledConfirmedAppointments::class => app(DetectRescheduledConfirmedAppointmentsCaster::class),
            default => null,
        };
    }
}
