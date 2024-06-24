<?php

declare(strict_types=1);

namespace App\View\Components;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RouteTimeline extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $route
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $duration = Carbon::parse($this->route['details']['start_at'])
            ->diffInMinutes(Carbon::parse($this->route['details']['end_at']));

        return view('components.route-timeline', [
            'duration' => $duration,
            'width' => $duration * 4 + 50,
            'title' => 'Route #' . $this->route['id'],
        ]);
    }
}
