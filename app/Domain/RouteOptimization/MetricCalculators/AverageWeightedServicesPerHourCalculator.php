<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class AverageWeightedServicesPerHourCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.25;

    public const AVERAGE_WEIGHTED_SERVICES_PER_ONE_POINT = 0.5;

    /**
     * Calculates average weighted services per hour metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $totalWorkingTimeInHours = $routeStats->getTotalWorkingTime()->getTotalMinutes() / 60;
        $averageWeightedServicesPerHour = $totalWorkingTimeInHours > 0
            ? round($routeStats->getTotalWeightedServices() / $totalWorkingTimeInHours, 1)
            : 0;

        // Determine the score of the metric
        $points = $averageWeightedServicesPerHour / self::AVERAGE_WEIGHTED_SERVICES_PER_ONE_POINT;
        $points = min($points, Score::MAX_POSSIBLE_SCORE);

        return new Metric(
            MetricKey::AVERAGE_WEIGHTED_SERVICES_PER_HOUR,
            $averageWeightedServicesPerHour,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
