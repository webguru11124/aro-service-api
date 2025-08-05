<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use Carbon\CarbonInterface;

interface OptimizationPostProcessService
{
    /**
     * Runs post optimization actions
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     *
     * @return void
     */
    public function execute(CarbonInterface $date, OptimizationState $optimizationState): void;
}
