<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\Tracking\ValueObjects\OptimizationStateMetrics;
use Tests\Tools\TestValue;

class OptimizationStateMetricsFactory extends AbstractFactory
{
    public function single($overrides = []): OptimizationStateMetrics
    {
        $totalDriveTime = $overrides['totalDriveTime'] ?? TestValue::TOTAL_DRIVE_TIME;
        $totalDriveMiles = $overrides['totalDriveMiles'] ?? TestValue::TOTAL_DRIVE_MILES;
        $optimizationScore = $overrides['optimizationScore'] ?? TestValue::OPTIMIZATION_SCORE;
        $totalWorkingHours = $overrides['totalWorkingHours'] ?? TestValue::TOTAL_WORKING_HOURS;
        $totalWeightedServices = $overrides['totalWeightedServices'] ?? TestValue::TOTAL_WEIGHTED_SERVICES;
        $averageTimeBetweenServices = $overrides['averageTimeBetweenServices'] ?? TestValue::AVERAGE_TIME_BETWEEN_SERVICES;
        $averageMilesBetweenServices = $overrides['averageMilesBetweenServices'] ?? TestValue::AVERAGE_MILES_BETWEEN_SERVICES;
        $averageWeightedServicesPerHour = $overrides['averageWeightedServicesPerHour'] ?? TestValue::AVERAGE_WEIGHTED_SERVICES_PER_HOUR;

        return new OptimizationStateMetrics(
            $totalDriveTime,
            $totalDriveMiles,
            $optimizationScore,
            $totalWorkingHours,
            $totalWeightedServices,
            $averageTimeBetweenServices,
            $averageMilesBetweenServices,
            $averageWeightedServicesPerHour
        );
    }
}
