<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ScheduledRoute extends Component
{
    public function __construct(
        private array $route,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.scheduled-route', [
            'routeId' => $this->route['id'],
            'employee' => $this->route['service_pro']['name'],
            'employeeId' => $this->route['service_pro']['id'],
            'skills' => implode(', ', $this->route['service_pro']['skills'] ?? []),
            'score' => $this->getScore(),
        ]);
    }

    private function getScore(): int|float
    {
        return !empty($this->route['details']['optimization_score'])
            ? round($this->route['details']['optimization_score'] * 100)
            : 0;
    }
}
