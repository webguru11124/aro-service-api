<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\OptimizationStateStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Tests\TestCase;

class OptimizationStateStatsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_array_of_values(): void
    {
        $stats = new OptimizationStateStats(
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(1),
            $this->faker->randomNumber(1),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
            $this->faker->randomFloat(2, 2, 5),
            $this->faker->randomFloat(2, 5, 10),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
        );

        $expectedArray = [
            'total_assigned_appointments' => $stats->getTotalAssignedAppointments(),
            'total_unassigned_appointments' => $stats->getTotalUnassignedAppointments(),
            'total_routes' => $stats->getTotalRoutes(),
            'total_drive_time' => $stats->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => $stats->getTotalDriveDistance()->getMiles(),
            'services_per_hour' => $stats->getServicesPerHour(),
            'average_daily_working_hours' => $stats->getAverageDailyWorkingHours(),
            'full_drive_time' => $stats->getFullDriveTime()->getTotalMinutes(),
            'full_drive_miles' => $stats->getFullDriveDistance()->getMiles(),
        ];

        $this->assertEquals($expectedArray, $stats->toArray());
    }
}
