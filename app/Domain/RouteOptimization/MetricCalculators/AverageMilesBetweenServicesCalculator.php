<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class AverageMilesBetweenServicesCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.075;
    public const GOLD_STANDARD_AVERAGE_MILES_BETWEEN_SERVICES = 4;
    public const EXTRA_MILES_FINE_PER_ONE_POINT = 0.8;

    /**
     * Calculates average miles between services metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $miles = $routeStats->getAverageDriveDistanceBetweenServices()->getMiles();

        // Determine the score of the metric
        $points = Score::MAX_POSSIBLE_SCORE - ($miles - self::GOLD_STANDARD_AVERAGE_MILES_BETWEEN_SERVICES) / self::EXTRA_MILES_FINE_PER_ONE_POINT;
        $points = max(Score::MIN_POSSIBLE_SCORE, $points);
        $points = min(Score::MAX_POSSIBLE_SCORE, $points);

        return new Metric(
            MetricKey::AVERAGE_MILES_BETWEEN_SERVICES,
            $miles,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
