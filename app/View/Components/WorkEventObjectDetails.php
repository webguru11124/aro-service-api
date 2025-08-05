<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Domain\RouteOptimization\Entities\WorkEvent\AbstractWorkEvent;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\View\Shared\WorkEventIcons;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WorkEventObjectDetails extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public WorkEvent $workEvent
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.work-event-object-details', [
            'workEventIcon' => WorkEventIcons::WORK_EVENT_ICONS[$this->workEvent->getType()->value] ?? '',
            'expectedTime' => $this->getExpectedTime(),
        ]);
    }

    private function getExpectedTime(): string
    {
        if ($this->workEvent instanceof AbstractWorkEvent) {
            $expectedArrival = $this->workEvent->getExpectedArrival();

            return $expectedArrival->getStartAt()->format('H:i') . ' - ' . $expectedArrival->getEndAt()->format('H:i');
        }

        return '';
    }
}
