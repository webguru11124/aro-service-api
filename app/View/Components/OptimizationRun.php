<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OptimizationRun extends Component
{
    private const DATADOG_LOGS_URL = 'https://us5.datadoghq.com/logs?';

    public function __construct(
        public int $stateIndex,
        private array $state,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.optimization-run', [
            'stateId' => $this->state['pre_state_id'],
            'resultStateId' => $this->state['result_state_id'] ?? null,
            'startAt' => $this->state['start_at'],
            'processing' => $this->state['processing'] ?? '',
            'routes' => $this->state['routes'],
            'score' => $this->getScore(),
            'engine' => $this->state['engine'],
            'routesCount' => count($this->state['routes'] ?? []),
            'appointmentsCount' => $this->state['total_assigned_appointments'] ?? 0,
            'unassignedCount' => $this->state['unassigned_appointments_count'] ?? 0,
            'overbookedCount' => $this->state['overbooked_appointments_count'] ?? 0,
            'datadogLink' => $this->getDatadogLogsLink(),
            'isSimulation' => $this->state['simulations'] ?? false,
            'appliedRules' => $this->state['rules'] ?? [],
            'weatherForecast' => $this->getWeatherForecast(),
            'stats' => $this->state['stats'] ?? [],
        ]);
    }

    private function getWeatherForecast(): array
    {
        return $this->state['weather_forecast'] ?? [];
    }

    private function getDatadogLogsLink(): string
    {
        $env = config('app.env');

        return self::DATADOG_LOGS_URL . http_build_query([
            'query' => "service:aro-route-optimization-queue-worker env:$env -RUNNING -FAIL -DONE -TRUNCATED",
            'cols' => 'host',
            'index' => '*',
            'messageDisplay' => 'inline',
            'refresh_mode' => 'paused',
            'stream_sort' => 'time,desc',
            'view' => 'spans',
            'viz' => 'stream',
            'live' => 'false',
            'from_ts' => $this->state['start_timestamp'],
            'to_ts' => $this->state['end_timestamp'],
        ]);
    }

    private function getScore(): int|float
    {
        return isset($this->state['metrics']['optimization_score'])
            ? $this->state['metrics']['optimization_score'] * 100
            : 0;
    }
}
