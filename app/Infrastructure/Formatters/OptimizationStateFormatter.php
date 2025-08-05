<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\OptimizationState;

interface OptimizationStateFormatter
{
    /**
     * Formats the optimization state in a given way
     *
     * @param OptimizationState $state
     *
     * @return mixed
     */
    public function format(OptimizationState $state): mixed;
}
