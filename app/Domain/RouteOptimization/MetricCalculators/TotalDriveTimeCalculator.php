<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class TotalDriveTimeCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.1;
    // Maximum of 5 points for 2 hours (120 mins) Total Drive Time or less.
    public const GOLD_STANDARD_OF_TOTAL_DRIVE_TIME = 120;
    // -1 point for every 12 mins over of gold standard.
    public const DRIVING_TIME_FINE = 12;

    /**
     * Calculates total drive time metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $totalDriveTime = $routeStats->getTotalDriveTime()->getTotalMinutes();

        // Determine the score of the metric
        $points = Score::MAX_POSSIBLE_SCORE - ($totalDriveTime - self::GOLD_STANDARD_OF_TOTAL_DRIVE_TIME) / self::DRIVING_TIME_FINE;
        $points = max(Score::MIN_POSSIBLE_SCORE, $points);
        $points = min(Score::MAX_POSSIBLE_SCORE, $points);

        return new Metric(
            MetricKey::TOTAL_DRIVE_TIME,
            $totalDriveTime,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
