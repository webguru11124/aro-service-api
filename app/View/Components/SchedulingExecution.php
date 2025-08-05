<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class SchedulingExecution extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        public string $link,
        private array $execution,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render()
    {
        return view('components.scheduling-execution', [
            'createdAt' => $this->execution['created_at'],
            'stats' => $this->execution['stats'] ?? null,
        ]);
    }
}
