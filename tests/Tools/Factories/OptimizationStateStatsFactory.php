<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\ValueObjects\OptimizationStateStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;

class OptimizationStateStatsFactory extends AbstractFactory
{
    public function single($overrides = []): OptimizationStateStats
    {

        $totalAssignedAppointments = $overrides['totalAssignedAppointments'] ?? $this->faker->randomNumber(2);
        $totalUnassignedAppointments = $overrides['totalUnassignedAppointments'] ?? $this->faker->randomNumber(2);
        $totalRoutes = $overrides['totalRoutes'] ?? $this->faker->randomNumber(2);
        $totalDriveTime = $this->createDuration($overrides['totalDriveTime'] ?? '1h 0m');
        $totalDriveDistance = $this->createDistance($overrides['totalDriveDistance'] ?? $this->faker->randomFloat(4, 10, 9999));
        $servicesPerHour = $overrides['servicesPerHour'] ?? $this->faker->randomFloat(2, 0, 10);
        $averageDailyWorkingHours = $overrides['averageDailyWorkingHours'] ?? $this->faker->randomFloat(2, 1, 12);
        $fullDriveTime = $this->createDuration($overrides['fullDriveTime'] ?? '1h 10m');
        $fullDriveDistance = $this->createDistance($overrides['fullDriveDistance'] ?? $this->faker->randomFloat(4, 10, 9999));

        return new OptimizationStateStats(
            $totalAssignedAppointments,
            $totalUnassignedAppointments,
            $totalRoutes,
            $totalDriveTime,
            $totalDriveDistance,
            $servicesPerHour,
            $averageDailyWorkingHours,
            $fullDriveTime,
            $fullDriveDistance,
        );

    }

    private function createDuration(string $totalDriveTimeString): Duration
    {
        $interval = CarbonInterval::fromString($totalDriveTimeString);

        return new Duration($interval);
    }

    private function createDistance(float $totalDriveDistanceString): Distance
    {
        return Distance::fromMeters($totalDriveDistanceString);
    }
}
