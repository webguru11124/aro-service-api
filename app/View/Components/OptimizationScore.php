<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OptimizationScore extends Component
{
    private const SCORE_LOW_LIMIT = 70;
    private const SCORE_HIGH_LIMIT = 90;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public mixed $score,
        public bool $hidden = false,
        public string $text = '',
        public string $elementId = '',
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.optimization-score', [
            'scoreRate0' => $this->score == 0,
            'scoreRate1' => $this->score > 0 && $this->score < self::SCORE_LOW_LIMIT,
            'scoreRate2' => $this->score >= self::SCORE_LOW_LIMIT && $this->score < self::SCORE_HIGH_LIMIT,
            'scoreRate3' => $this->score >= self::SCORE_HIGH_LIMIT,
            'hidden' => $this->hidden,
            'text' => $this->text,
            'elementId' => $this->elementId,
        ]);
    }
}
