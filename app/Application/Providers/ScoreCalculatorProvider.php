<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\RouteOptimization\MetricCalculators\AverageMilesBetweenServicesCalculator;
use App\Domain\RouteOptimization\MetricCalculators\AverageTimeBetweenServicesCalculator;
use App\Domain\RouteOptimization\MetricCalculators\AverageWeightedServicesPerHourCalculator;
use App\Domain\RouteOptimization\MetricCalculators\TotalDriveMilesCalculator;
use App\Domain\RouteOptimization\MetricCalculators\TotalDriveTimeCalculator;
use App\Domain\RouteOptimization\MetricCalculators\TotalWeightedServicesCalculator;
use App\Domain\RouteOptimization\MetricCalculators\TotalWorkingHoursCalculator;
use App\Domain\RouteOptimization\Services\RouteOptimizationScoreCalculationService;
use Illuminate\Support\ServiceProvider;

class ScoreCalculatorProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->when(RouteOptimizationScoreCalculationService::class)
            ->needs('$calculators')
            ->give(function ($app) {
                return [
                    $app->make(TotalWeightedServicesCalculator::class),
                    $app->make(TotalWorkingHoursCalculator::class),
                    $app->make(AverageTimeBetweenServicesCalculator::class),
                    $app->make(AverageMilesBetweenServicesCalculator::class),
                    $app->make(AverageWeightedServicesPerHourCalculator::class),
                    $app->make(TotalDriveTimeCalculator::class),
                    $app->make(TotalDriveMilesCalculator::class),
                ];
            });
    }
}
