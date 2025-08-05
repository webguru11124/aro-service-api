<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\PostOptimizationRules\SetAppointmentEstimatedDuration;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Illuminate\Support\Collection;
use Carbon\CarbonInterface;

class PestRoutesAppointmentEstimatedDurationCaster extends AbstractPestRoutesPostOptimizationRuleCaster
{
    use PestRoutesAppointmentProcessor;

    private const FEATURE_FLAG = 'isSetAppointmentEstimatedDurationEnabled';

    private SetAppointmentEstimatedDuration $rule;
    private int $officeId;

    public function __construct(
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Updates appointments notes in PestRoutes to add minimum, maximum and optimal duration
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     * @param SetAppointmentEstimatedDuration $rule
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

        $this->updateAppointmentDurationOnRoute($optimizationState);

        return $this->buildSuccessExecutionResult($rule);
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    private function updateAppointmentDurationOnRoute(OptimizationState $optimizationState): void
    {
        $appointmentsGroupedByRoute = $this->getAllAppointments($this->officeId, $optimizationState->getRoutes());

        foreach ($optimizationState->getRoutes() as $route) {
            $this->processRouteAppointments($route, $appointmentsGroupedByRoute);
        }
    }

    /**
     * @param Route $route
     * @param Collection<PestRoutesAppointment> $appointments
     *
     * @return void
     */
    private function processRouteAppointments(Route $route, Collection $appointments): void
    {
        $pestRoutesAppointments = $appointments->keyBy('id');

        /** @var Appointment $appointment */
        foreach ($route->getAppointments() as $appointment) {
            $pestRoutesAppointment = $pestRoutesAppointments->get($appointment->getId());
            $duration = $this->rule->roundDuration($appointment->getDuration()->getTotalMinutes());
            $notes = $this->rule->generateDurationNotes($appointment, $pestRoutesAppointment->notes ?? '');

            $this->appointmentsDataProcessor->update(
                $this->officeId,
                new UpdateAppointmentsParams(
                    appointmentId: $pestRoutesAppointment->id,
                    duration: $duration,
                    notes: $notes
                )
            );
        }
    }
}
