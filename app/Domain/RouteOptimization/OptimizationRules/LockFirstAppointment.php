<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Services\BusinessDaysService;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Illuminate\Support\Facades\Log;

class LockFirstAppointment extends AbstractGeneralOptimizationRule
{
    public function __construct(
        private BusinessDaysService $businessDaysService,
    ) {
    }

    /**
     * Rule to lock the first appointment of each route
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        if (!$this->businessDaysService->needsFirstAppointmentLock($optimizationState->getDate())) {
            return $this->buildTriggeredExecutionResult();
        }

        foreach ($optimizationState->getRoutes() as $route) {
            if ($this->canBeLocked($route)) {
                $this->lockFirstAppointment($route);
                $this->shiftUnlockedAppointmentsExpectedArrival($route);
            }
        }

        return $this->buildSuccessExecutionResult();
    }

    private function canBeLocked(Route $route): bool
    {
        if ($route->getAppointments()->isEmpty()) {
            return false;
        }

        /** @var Appointment $firstAppointment */
        $firstAppointment = $route->getAppointments()->first();
        $firstAppointmentEndAt = $firstAppointment->getTimeWindow()->getEndAt();

        /** @var Appointment $appointment */
        $appointment = $route->getAppointments()->first(
            fn (Appointment $appointment) => $firstAppointment->getId() !== $appointment->getId()
                && $firstAppointmentEndAt->greaterThan($appointment->getExpectedArrival()->getEndAt())
        );

        // TODO: remove or update this log after AARO-667 will be fixed
        if (!is_null($appointment)) {
            Log::warning(__('messages.routes_optimization.cant_lock_appointment', [
                'id' => $firstAppointment->getId(),
                'route_id' => $route->getId(),
                'office_id' => $route->getOfficeId(),
                'date' => $firstAppointment->getTimeWindow()->getStartAt()->toDateString(),
            ]), [
                'first_appointment' => [
                    'id' => $firstAppointment->getId(),
                    'start_at' => $firstAppointment->getTimeWindow()->getStartAt()->toTimeString(),
                    'end_at' => $firstAppointment->getTimeWindow()->getEndAt()->toTimeString(),
                    'expected_start_at' => $firstAppointment->getExpectedArrival()->getStartAt()->toTimeString(),
                    'expected_end_at' => $firstAppointment->getExpectedArrival()->getEndAt()->toTimeString(),
                ],
                'overlap_appointment' => [
                    'id' => $appointment->getId(),
                    'start_at' => $appointment->getTimeWindow()->getStartAt()->toTimeString(),
                    'end_at' => $appointment->getTimeWindow()->getEndAt()->toTimeString(),
                    'expected_start_at' => $appointment->getExpectedArrival()->getStartAt()->toTimeString(),
                    'expected_end_at' => $appointment->getExpectedArrival()->getEndAt()->toTimeString(),
                ],
            ]);
        }

        return is_null($appointment);
    }

    private function lockFirstAppointment(Route $route): void
    {
        /** @var Appointment $firstAppointment */
        $firstAppointment = $route->getAppointments()->first();
        $servicePro = $route->getServicePro();
        $timeWindow = $firstAppointment->getTimeWindow();

        $firstAppointment->lock($timeWindow, $servicePro);
    }

    private function shiftUnlockedAppointmentsExpectedArrival(Route $route): void
    {
        $appointments = $route->getAppointments();

        /** @var Appointment $firstAppointment */
        $firstAppointment = $appointments->first();

        if ($firstAppointment === null || !$firstAppointment->isLocked()) {
            return;
        }

        $firstAppointmentExpectedArrivalTopThreshold = $firstAppointment->getExpectedArrival()->getEndAt();

        /** @var Appointment $appointment */
        foreach ($appointments as $appointment) {
            if ($appointment->isLocked()) {
                continue;
            }

            $expectedArrival = $appointment->getExpectedArrival();

            if ($expectedArrival->getStartAt() > $firstAppointmentExpectedArrivalTopThreshold) {
                continue;
            }

            $appointment->setExpectedArrival(new TimeWindow(
                $firstAppointmentExpectedArrivalTopThreshold->clone()->addMinute(),
                $expectedArrival->getEndAt()
            ));
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Lock First Appointment';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule locks the first appointment of each route to prevent it from being moved. Thus allowing the Service Pro to know where they will start thier day from prior to leaving their home.';
    }
}
