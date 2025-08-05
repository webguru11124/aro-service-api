<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class TotalDriveMilesCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.05;
    // Maximum of 5 points for 60 miles Total Drive Miles or less.
    public const GOLD_STANDARD_OF_TOTAL_DRIVE_MILES = 60;
    // -1 point for every 10 miles over of gold standard.
    public const DRIVING_MILES_FINE = 10;

    /**
     * Calculates total drive miles metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $totalDriveMiles = $routeStats->getTotalDriveDistance()->getMiles();

        // Determine the score of the metric
        $points = Score::MAX_POSSIBLE_SCORE - ($totalDriveMiles - self::GOLD_STANDARD_OF_TOTAL_DRIVE_MILES) / self::DRIVING_MILES_FINE;
        $points = max(Score::MIN_POSSIBLE_SCORE, $points);
        $points = min(Score::MAX_POSSIBLE_SCORE, $points);

        return new Metric(
            MetricKey::TOTAL_DRIVE_MILES,
            $totalDriveMiles,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
