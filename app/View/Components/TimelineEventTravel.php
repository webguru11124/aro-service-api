<?php

declare(strict_types=1);

namespace App\View\Components;

use App\View\Shared\TimelineEventAttributes;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimelineEventTravel extends Component
{
    use TimelineEventAttributes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $event,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $params = $this->getEventParams($this->event);
        $params['title'] = $this->event['description'] . ': ' . $this->event['travel_miles'] . ' miles';

        return view('components.timeline-event-travel', $params);
    }
}