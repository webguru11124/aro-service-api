<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Tests\TestCase;

class RouteStatsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_array_of_values(): void
    {
        $stats = new RouteStats(
            $this->faker->randomNumber(1),
            $this->faker->randomNumber(1),
            $this->faker->randomNumber(1),
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(2),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
        );

        $expectedArray = [
            'total_initials' => $stats->getTotalInitials(),
            'total_reservice' => $stats->getTotalReservice(),
            'total_regular' => $stats->getTotalRegular(),
            'total_appointments' => $stats->getTotalAppointments(),
            'total_weighted_services' => $stats->getTotalWeightedServices(),
            'total_service_time_minutes' => $stats->getTotalServiceTime()->getTotalMinutes(),
            'total_working_time_minutes' => $stats->getTotalWorkingTime()->getTotalMinutes(),
            'total_break_time_minutes' => $stats->getTotalBreakTime()->getTotalMinutes(),
            'total_drive_time_minutes' => $stats->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => $stats->getTotalDriveDistance()->getMiles(),
            'average_drive_time_minutes' => $stats->getAverageDriveTimeBetweenServices()->getTotalMinutes(),
            'average_drive_miles' => $stats->getAverageDriveDistanceBetweenServices()->getMiles(),
            'full_drive_time_minutes' => $stats->getFullDriveTime()->getTotalMinutes(),
            'full_drive_miles' => $stats->getFullDriveDistance()->getMiles(),
        ];

        $this->assertEquals($expectedArray, $stats->toArray());
    }
}
