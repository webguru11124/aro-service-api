<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class AverageTimeBetweenServicesCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.075;
    public const GOLD_STANDARD_AVERAGE_TIME_BETWEEN_SERVICES = 8;
    public const EXTRA_TIME_FINE_PER_ONE_POINT = 1.6;

    /**
     * Calculates average time between services metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $timeBetweenServices = $routeStats->getTotalWorkingTime()
            ->decrease($routeStats->getTotalServiceTime())
            ->getTotalMinutes();
        $averageTime = $routeStats->getTotalAppointments() > 1
            ? $timeBetweenServices / ($routeStats->getTotalAppointments() - 1)
            : $timeBetweenServices;

        // Determine the score of the metric
        $points = Score::MAX_POSSIBLE_SCORE - ($averageTime - self::GOLD_STANDARD_AVERAGE_TIME_BETWEEN_SERVICES) / self::EXTRA_TIME_FINE_PER_ONE_POINT;
        $points = max(Score::MIN_POSSIBLE_SCORE, $points);
        $points = min(Score::MAX_POSSIBLE_SCORE, $points);

        return new Metric(
            MetricKey::AVERAGE_TIME_BETWEEN_SERVICES,
            $averageTime,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
