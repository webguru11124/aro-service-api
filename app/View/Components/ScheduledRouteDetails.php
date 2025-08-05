<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ScheduledRouteDetails extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $preRoute,
        public array $planRoute,
        public array $postRoute,
        public array $simRoutes,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.scheduled-route-details', [
            'routeId' => $this->preRoute['id'],
            'employee' => $this->preRoute['service_pro']['name'],
            'employeeId' => $this->preRoute['service_pro']['id'],
            'skills' => implode(', ', $this->preRoute['service_pro']['skills'] ?? []),
            'workingTime' => $this->getWorkingTime(),
            'routeType' => $this->preRoute['details']['route_type'] ?? '',
            'stats' => $this->getStats(),
            'score' => $this->getScore(),
            'metrics' => $this->getMetrics(),
        ]);
    }

    private function getScore(): int|float
    {
        $score = match (true) {
            !empty($this->postRoute['details']['optimization_score']) => $this->postRoute['details']['optimization_score'],
            !empty($this->simRoutes[0]['details']['optimization_score']) => $this->simRoutes[0]['details']['optimization_score'],
            default => 0,
        };

        return $score * 100;
    }

    private function getMetrics(): array
    {
        return match (true) {
            !empty($this->postRoute['metrics']) => $this->postRoute['metrics'],
            !empty($this->simRoutes[0]['metrics']) => $this->simRoutes[0]['metrics'],
            default => [],
        };
    }

    private function getStats(): array
    {
        return match (true) {
            !empty($this->postRoute['stats']) => $this->postRoute['stats'],
            !empty($this->simRoutes[0]['stats']) => $this->simRoutes[0]['stats'],
            default => [],
        };
    }

    private function getWorkingTime(): string
    {
        $workingHours = $this->postRoute['service_pro']['working_hours']
            ?? $this->simRoutes[0]['service_pro']['working_hours']
            ?? $this->preRoute['service_pro']['working_hours'];

        return $workingHours['start_at'] . ' - ' . $workingHours['end_at'];
    }
}
