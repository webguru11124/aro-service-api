<?php

declare(strict_types=1);

namespace App\View\Components;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RouteDetails extends Component
{
    public function __construct(
        public string $routeTitle,
        private array $route
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.route-details', [
            'routeStartTime' => Carbon::parse($this->route['details']['start_at'])->format('Y-m-d H:i'),
            'routeEndTime' => Carbon::parse($this->route['details']['end_at'])->format('H:i'),
            'schedule' => $this->route['schedule'],
            'capacity' => $this->route['service_pro']['capacity'] ?? $this->route['details']['capacity'] ?? 0,
            'maxCapacity' => $this->route['details']['max_capacity'] ?? '-',
            'actualCapacity' => $this->route['details']['actual_capacity'] ?? '-',
            'appointmentsCount' => $this->route['stats']['total_appointments'] ?? 0,
        ]);
    }
}
