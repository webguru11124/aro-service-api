<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;

class DetectRescheduledConfirmedAppointmentsCaster extends AbstractPestRoutesPostOptimizationRuleCaster
{
    /**
     * Detect if confirmed appointments were rescheduled
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     * @param PostOptimizationRule $rule
     *
     * @return RuleExecutionResult
     */
    public function process(
        CarbonInterface $date,
        OptimizationState $optimizationState,
        PostOptimizationRule $rule
    ): RuleExecutionResult {
        $confirmedAppointments = $optimizationState->getUnassignedAppointments()->filter(
            fn (Appointment $appointment) => $appointment->isNotified(),
        );

        if ($confirmedAppointments->isNotEmpty()) {
            $confirmedAppointmentIds = $confirmedAppointments->map(
                fn (Appointment $appointment) => $appointment->getId(),
            )->toArray();

            Log::warning(__('messages.routes_optimization.confirmed_appointments_detected_on_rescheduled_routes', [
                'count' => $confirmedAppointments->count(),
                'appointment_ids' => implode(', ', $confirmedAppointmentIds),
                'office' => $optimizationState->getOffice()->getName(),
                'office_id' => $optimizationState->getOffice()->getId(),
                'date' => $date->toDateString(),
            ]));
        }

        return $this->buildSuccessExecutionResult($rule);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Detect Rescheduled Confirmed Appointments';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Detect If Confirmed Appointments Were On Rescheduled Routes';
    }
}
