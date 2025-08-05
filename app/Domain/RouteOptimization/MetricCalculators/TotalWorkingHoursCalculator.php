<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;

class TotalWorkingHoursCalculator implements RouteMetricCalculator
{
    public const METRIC_WEIGHT = 0.2;
    // The ideal number of working hours for service pro that gives us max points
    public const GOLD_STANDARD_OF_TOTAL_WORKING_HOURS = 8;
    // -1 point for every 1.6 hours over or under of gold standard.
    public const TOTAL_WORKING_HOURS_FINE = 1.6;

    /**
     * Calculates total working hours metric
     *
     * @param RouteStats $routeStats
     *
     * @return Metric
     */
    public function calculate(RouteStats $routeStats): Metric
    {
        // Calculate the raw value of the metric
        $totalWorkingTimeInHours = $routeStats->getTotalWorkingTime()->getTotalMinutes() / 60;

        // Determine the score of the metric
        $hoursDifferenceFromStandard = abs(self::GOLD_STANDARD_OF_TOTAL_WORKING_HOURS - $totalWorkingTimeInHours);
        $points = Score::MAX_POSSIBLE_SCORE - $hoursDifferenceFromStandard / self::TOTAL_WORKING_HOURS_FINE;

        if ($points < Score::MIN_POSSIBLE_SCORE) {
            $points = Score::MIN_POSSIBLE_SCORE;
        }

        return new Metric(
            MetricKey::TOTAL_WORKING_HOURS,
            $totalWorkingTimeInHours,
            new Weight(self::METRIC_WEIGHT),
            new Score($points)
        );
    }
}
