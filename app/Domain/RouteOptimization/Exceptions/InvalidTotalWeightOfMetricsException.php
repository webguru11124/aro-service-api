<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Exceptions;

use Exception;

class InvalidTotalWeightOfMetricsException extends Exception
{
    /**
     * @param float|int $totalWeight
     *
     * @return InvalidTotalWeightOfMetricsException
     */
    public static function instance(float|int $totalWeight): InvalidTotalWeightOfMetricsException
    {
        return new self(__('messages.routes_optimization.invalid_total_metrics_weight', [
            'total_weight' => $totalWeight,
        ]));
    }
}
