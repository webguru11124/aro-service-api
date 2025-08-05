<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;

class OverviewNavigation extends Component
{
    public function __construct(
        public string|null $processDate = null,
        public string|null $executionDate = null,
        public int|null $officeId = null,
    ) {
    }

    public function render()
    {
        return view('components.overview-navigation');
    }
}
