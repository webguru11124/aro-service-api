<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

interface RouteMetricCalculator
{
    /**
     * Calculate a metric from given route statistics
     */
    public function calculate(RouteStats $routeStats): Metric;
}
