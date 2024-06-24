<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Average;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\Entities\Office;
use Illuminate\Support\Collection;

class OptimizationStateArrayFormatter implements OptimizationStateFormatter
{
    private OptimizationState $state;

    public function __construct(
        private WeatherInfoFormatter $weatherInfoFormatter
    ) {
    }

    /**
     * Formats the optimization state as an array
     *
     * @param OptimizationState $state
     *
     * @return mixed[]
     */
    public function format(OptimizationState $state): array
    {
        $this->setOptimizationState($state);

        return $this->formatOptimizationStateAsArray();
    }

    private function setOptimizationState(OptimizationState $state): void
    {
        $this->state = $state;
    }

    /**
     * @return mixed[]
     */
    private function formatOptimizationStateAsArray(): array
    {
        return [
            'state' => [
                'engine' => $this->state->getEngine()->value,
                'params' => $this->formatOptimizationParams($this->state->getOptimizationParams()),
                'optimization_window_start' => $this->state->getOptimizationTimeFrame()->getStartAt()->toDateTimeString(),
                'optimization_window_end' => $this->state->getOptimizationTimeFrame()->getEndAt()->toDateTimeString(),
                'created_at' => $this->state->getCreatedAt()->timestamp,
                'unassigned_appointments' => $this->formatUnassignedAppointmentsArray(),
            ],
            'office' => $this->formatOffice($this->state->getOffice()),
            'metrics' => $this->formatAverageScores($this->state->getAverageScores()),
            'rules' => $this->formatRules($this->state->getRuleExecutionResults()),
            'weather' => $this->formatWeatherInfo($this->state->getWeatherInfo()),
        ];
    }

    /**
     * @param WeatherInfo|null $weatherInfo
     *
     * @return array<string, mixed>
     */
    private function formatWeatherInfo(WeatherInfo|null $weatherInfo): array
    {
        if ($weatherInfo === null) {
            return [];
        }

        return $this->weatherInfoFormatter->format($weatherInfo);
    }

    /**
     * @return mixed[]
     */
    private function formatUnassignedAppointmentsArray(): array
    {
        $formattedUnassignedAppointments = [];
        /** @var Appointment $unassignedAppointment */
        foreach($this->state->getUnassignedAppointments() as $unassignedAppointment) {
            $formattedUnassignedAppointments[] = [
                'id' => $unassignedAppointment->getId(),
            ];
        }

        return $formattedUnassignedAppointments;
    }

    /**
     * @param Collection<Average> $averageScores
     *
     * @return mixed[]
     */
    private function formatAverageScores(Collection $averageScores): array
    {
        return $averageScores->mapWithKeys(function (Average $average) {
            return [$average->getKey()->value => $average->getScore()->value()];
        })->toArray();
    }

    /**
     * @param OptimizationParams $params
     *
     * @return mixed[]
     */
    private function formatOptimizationParams(OptimizationParams $params): array
    {
        return [
            'simulation_run' => $params->simulationRun,
            'build_planned_optimization' => $params->buildPlannedOptimization,
            'last_optimization_run' => $params->lastOptimizationRun,
        ];
    }

    /**
     * @param Collection $ruleExecutionResults
     *
     * @return mixed[]
     */
    private function formatRules(Collection $ruleExecutionResults): array
    {
        return $ruleExecutionResults->map(fn (RuleExecutionResult $ruleExecutionResult) => [
            'id' => $ruleExecutionResult->getRuleId(),
            'name' => $ruleExecutionResult->getRuleName(),
            'description' => $ruleExecutionResult->getRuleDescription(),
            'is_applied' => $ruleExecutionResult->isApplied(),
            'is_triggered' => $ruleExecutionResult->isTriggered(),
        ])->toArray();
    }

    /**
     * @return mixed[]
     */
    private function formatOffice(Office $office): array
    {
        return [
            'office_id' => $office->getId(),
            'office' => $office->getName(),
        ];
    }
}
