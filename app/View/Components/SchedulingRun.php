<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SchedulingRun extends Component
{
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
        return view('components.scheduling-run', [
            'stateId' => $this->state['id'],
            'startAt' => $this->state['start_at'],
            'stats' => $this->state['stats'] ?? [],
        ]);
    }
}
