<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OptimizationExecution extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $link,
        private array $execution,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $score = isset($this->execution['optimization_score']) ? $this->execution['optimization_score'] * 100 : 0;

        return view('components.optimization-execution', [
            'success' => $this->execution['success'],
            'createdAt' => $this->execution['created_at'],
            'score' => $score,
            'stats' => $this->execution['stats'] ?? null,
        ]);
    }
}
