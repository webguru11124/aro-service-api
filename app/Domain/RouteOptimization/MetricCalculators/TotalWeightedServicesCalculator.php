<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class TotalWeightedServicesCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.25;
    // The number of weighted services needed to get +1 point of score
    public const WEIGHTED_SERVICES_PER_ONE_POINT = 2.8;
    // The ideal number of weighted services on a route that gives us max points
    public const GOLD_STANDARD_NUMBER_OF_WEIGHTED_SERVICES = 14;

    /**
     * Calculates total weighted services metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $totalWeightedServices = $routeStats->getTotalWeightedServices();

        // Determine the score of the metric
        if ($totalWeightedServices >= self::GOLD_STANDARD_NUMBER_OF_WEIGHTED_SERVICES) {
            $points = Score::MAX_POSSIBLE_SCORE;
        } else {
            $points = $totalWeightedServices / self::WEIGHTED_SERVICES_PER_ONE_POINT;
        }

        return new Metric(
            MetricKey::TOTAL_WEIGHTED_SERVICES,
            $totalWeightedServices,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
