<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\PostOptimizationRules\SetStaticTimeWindows;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use Illuminate\Support\Collection;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentTimeWindow;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Carbon\CarbonInterface;

class PestRoutesStaticTimeWindowsCaster extends AbstractPestRoutesPostOptimizationRuleCaster
{
    use PestRoutesAppointmentProcessor;

    private const FEATURE_FLAG = 'isStaticTimeWindowForAppointmentsEnabled';

    private SetStaticTimeWindows $rule;
    private int $officeId;

    public function __construct(
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Updates anytime appointments in PestRoutes to have specific time window
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     * @param SetStaticTimeWindows $rule
     *
     * @return RuleExecutionResult
     */
    public function process(
        CarbonInterface $date,
        OptimizationState $optimizationState,
        PostOptimizationRule $rule
    ): RuleExecutionResult {
        $this->rule = $rule;
        $this->officeId = $optimizationState->getOffice()->getId();

        $isRuleApplicable
            = $optimizationState->isLastOptimizationRun()
            && $this->featureFlagService->isFeatureEnabledForOffice($this->officeId, self::FEATURE_FLAG);

        if (!$isRuleApplicable) {
            return $this->buildTriggeredExecutionResult($rule);
        }

        $this->updateAppointmentTimeWindowsOnRoute($optimizationState);

        return $this->buildSuccessExecutionResult($rule);
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    private function updateAppointmentTimeWindowsOnRoute(OptimizationState $optimizationState): void
    {
        $filterCallback = fn (PestRoutesAppointment $appointment) => $appointment->timeWindow != AppointmentTimeWindow::Timed;
        $filteredAppointments = $this->getAllAppointments($this->officeId, $optimizationState->getRoutes(), $filterCallback);

        foreach ($optimizationState->getRoutes() as $route) {
            $this->processRouteAppointments($route, $filteredAppointments);
        }
    }

    private function processRouteAppointments(Route $route, Collection $pestRoutesAppointments): void
    {
        /** @var Appointment $appointment */
        foreach ($route->getAppointments() as $appointment) {
            if ($appointment === null) {
                continue;
            }

            if (!$pestRoutesAppointments->contains('id', $appointment->getId())) {
                continue;
            }

            $newTimeWindow = $this->rule->calculateStaticTimeWindow($appointment);

            if ($newTimeWindow === null) {
                continue;
            }

            $this->appointmentsDataProcessor->update(
                $this->officeId,
                new UpdateAppointmentsParams(
                    appointmentId: $appointment->getId(),
                    start: $newTimeWindow->getStartAt()->toDateTime(),
                    end: $newTimeWindow->getEndAt()->toDateTime(),
                )
            );
        }
    }
}
