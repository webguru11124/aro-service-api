<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class ShiftLockedAppointmentsTimeWindow extends AbstractAdditionalOptimizationRule
{
    private const TIME_WINDOW_SHIFT_MINUTES = 30;

    /**
     * Rule to shift the time window of locked appointments
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

        $unassignedLockedAppointmentsIds = $resultOptimizationState
            ->getUnassignedAppointments()
            ->filter(fn (Appointment $appointment) => $appointment->isLocked())
            ->map(fn (Appointment $appointment) => $appointment->getId())
            ->toArray();

        if (empty($unassignedLockedAppointmentsIds)) {
            $sourceOptimizationState->addRuleExecutionResults(collect([
                $this->buildTriggeredExecutionResult(),
            ]));

            return;
        }

        $isRuleApplied = false;

        /** @var Route $route */
        foreach ($sourceOptimizationState->getRoutes() as $route) {
            /** @var Appointment|null $firstAppointment */
            $firstAppointment = $route->getAppointments()->first();

            if ($firstAppointment === null) {
                continue;
            }

            if (!in_array($firstAppointment->getId(), $unassignedLockedAppointmentsIds)) {
                continue;
            }

            $this->shiftAppointmentExpectedArrival($firstAppointment);
            $isRuleApplied = true;
        }

        $sourceOptimizationState->addRuleExecutionResults(collect([
            $isRuleApplied ? $this->buildSuccessExecutionResult() : $this->buildTriggeredExecutionResult(),
        ]));
    }

    private function shiftAppointmentExpectedArrival(Appointment $appointment): void
    {
        $oldStart = $appointment->getExpectedArrival()->getStartAt();
        $oldEnd = $appointment->getExpectedArrival()->getEndAt();

        $appointment->setExpectedArrival(new TimeWindow(
            $oldStart,
            $oldEnd->clone()->addMinutes(self::TIME_WINDOW_SHIFT_MINUTES)
        ));
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Shift Locked Appointments Time Window';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule shifts the time window of locked appointments by ' . self::TIME_WINDOW_SHIFT_MINUTES . ' minutes.';
    }
}
