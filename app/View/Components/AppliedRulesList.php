<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppliedRulesList extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $rules,
        public bool $hidden = false,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.applied-rules-list', [
            'rules' => $this->rules,
            'hidden' => $this->hidden,
        ]);
    }
}
