<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\ValueObjects;

use App\Domain\Tracking\ValueObjects\OptimizationStateMetrics;
use Tests\TestCase;

class OptimizationStateMetricsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_expected_getters(): void
    {
        $totalDriveTime = 10.5;
        $totalDriveMiles = 200.3;
        $optimizationScore = 0.85;
        $totalWorkingHours = 8.25;
        $totalWeightedServices = 15.75;
        $averageTimeBetweenServices = 2.5;
        $averageMilesBetweenServices = 50.6;
        $averageWeightedServicesPerHour = 1.9;

        $optimizationMetrics = new OptimizationStateMetrics(
            $totalDriveTime,
            $totalDriveMiles,
            $optimizationScore,
            $totalWorkingHours,
            $totalWeightedServices,
            $averageTimeBetweenServices,
            $averageMilesBetweenServices,
            $averageWeightedServicesPerHour
        );

        $this->assertEquals($totalDriveTime, $optimizationMetrics->getTotalDriveTime());
        $this->assertEquals($totalDriveMiles, $optimizationMetrics->getTotalDriveMiles());
        $this->assertEquals($optimizationScore, $optimizationMetrics->getOptimizationScore());
        $this->assertEquals($totalWorkingHours, $optimizationMetrics->getTotalWorkingHours());
        $this->assertEquals($totalWeightedServices, $optimizationMetrics->getTotalWeightedServices());
        $this->assertEquals($averageTimeBetweenServices, $optimizationMetrics->getAverageTimeBetweenServices());
        $this->assertEquals($averageMilesBetweenServices, $optimizationMetrics->getAverageMilesBetweenServices());
        $this->assertEquals($averageWeightedServicesPerHour, $optimizationMetrics->getAverageWeightedServicesPerHour());
    }
}
