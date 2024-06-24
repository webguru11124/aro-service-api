<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\PostOptimizationRules\SetExpectServiceTimeWindow;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentTimeWindow;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PestRoutesExpectServiceTimeWindowCaster extends AbstractPestRoutesPostOptimizationRuleCaster
{
    private const FEATURE_FLAG = 'isExpectServiceTimeWindowAdjustmentEnabled';
    private const MINUTE_ROUND_INTERVAL = 15;
    private const TIME_WINDOW_UPDATE_DEBUG_MESSAGE = 'Appointment time window adjustment';

    private SetExpectServiceTimeWindow $rule;

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
     * @param SetExpectServiceTimeWindow $rule
     *
     * @return RuleExecutionResult
     */
    public function process(
        CarbonInterface $date,
        OptimizationState $optimizationState,
        PostOptimizationRule $rule
    ): RuleExecutionResult {
        $this->rule = $rule;
        $officeId = $optimizationState->getOffice()->getId();

        $isRuleApplicable
            = $optimizationState->isLastOptimizationRun()
            && $this->featureFlagService->isFeatureEnabledForOffice($officeId, self::FEATURE_FLAG);

        if (!$isRuleApplicable) {
            return $this->buildTriggeredExecutionResult($rule);
        }

        /** @var Route $route */
        foreach ($optimizationState->getRoutes() as $route) {
            $this->updateAppointmentTimeWindowsOnRoute($officeId, $route);
        }

        return $this->buildSuccessExecutionResult($rule);
    }

    private function updateAppointmentTimeWindowsOnRoute(int $officeId, Route $route): void
    {
        $pestRoutesAppointments = $this->getAppointments($officeId, $route->getId());

        foreach ($pestRoutesAppointments as $pestRoutesAppointment) {
            $appointment = $this->getAppointmentById($route, $pestRoutesAppointment->id);

            if ($appointment === null) {
                continue;
            }

            $newTimeWindow = $this->getTimeWindow($appointment);
            $this->setAppointmentTimeWindowInPestRoutes($pestRoutesAppointment, $newTimeWindow);
            $this->logAppointmentTimeWindowChange($pestRoutesAppointment, $appointment, $newTimeWindow);
        }
    }

    private function setAppointmentTimeWindowInPestRoutes(
        PestRoutesAppointment $appointment,
        TimeWindow $timeWindow
    ): void {
        $this->appointmentsDataProcessor->update(
            $appointment->officeId,
            new UpdateAppointmentsParams(
                appointmentId: $appointment->id,
                start: $timeWindow->getStartAt()->toDateTime(),
                end: $timeWindow->getEndAt()->toDateTime(),
            )
        );
    }

    private function getTimeWindow(Appointment $appointment): TimeWindow
    {
        $startAt = $this->getNearestTime($appointment->getTimeWindow()->getStartAt());

        return new TimeWindow(
            $startAt,
            $startAt->clone()->addMinutes($this->rule->getTimeWindowMinutes()->getTotalMinutes())
        );
    }

    private function getNearestTime(CarbonInterface $time): CarbonInterface
    {
        return $time->clone()->setTime(
            $time->hour,
            (int) round($time->minute / self::MINUTE_ROUND_INTERVAL) * self::MINUTE_ROUND_INTERVAL
        );
    }

    private function getAppointmentById(Route $route, int $id): Appointment|null
    {
        return $route->getAppointments()->first(fn (Appointment $appointment) => $appointment->getId() === $id);
    }

    /**
     * @param int $officeId
     * @param int $routeId
     *
     * @return Collection<PestRoutesAppointment>
     */
    private function getAppointments(int $officeId, int $routeId): Collection
    {
        return $this->appointmentsDataProcessor->extract($officeId, new SearchAppointmentsParams(
            officeIds: [$officeId],
            status: AppointmentStatus::Pending,
            routeIds: [$routeId],
        ))->filter(fn (PestRoutesAppointment $appointment) => $appointment->timeWindow != AppointmentTimeWindow::Timed);
    }

    private function logAppointmentTimeWindowChange(
        PestRoutesAppointment $pestRoutesAppointment,
        Appointment $appointment,
        TimeWindow $newTimeWindow
    ): void {
        if (config('app.debug')) {
            Log::debug(self::TIME_WINDOW_UPDATE_DEBUG_MESSAGE, [
                'id' => $pestRoutesAppointment->id,
                'pest_start_at' => $pestRoutesAppointment->start->format('H:i:s'),
                'pest_end_at' => $pestRoutesAppointment->end->format('H:i:s'),
                'expected_start_at' => $appointment->getTimeWindow()->getStartAt()->toTimeString(),
                'expected_end_at' => $appointment->getTimeWindow()->getEndAt()->toTimeString(),
                'updated_start_at' => $newTimeWindow->getStartAt()->toTimeString(),
                'updated_end_at' => $newTimeWindow->getEndAt()->toTimeString(),
            ]);
        }
    }
}
