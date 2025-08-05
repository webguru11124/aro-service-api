<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;

class RouteStatsFactory
{
    /**
     * Creates RouteStatsDTO object from data array
     *
     * @param array<string, mixed> $routeStatsData
     *
     * @return RouteStats
     */
    public function create(array $routeStatsData): RouteStats
    {
        return new RouteStats(
            $routeStatsData['total_initials'],
            $routeStatsData['total_reservice'],
            $routeStatsData['total_regular'],
            $routeStatsData['total_appointments'],
            $routeStatsData['total_weighted_services'],
            new Duration(CarbonInterval::minutes($routeStatsData['total_service_time_minutes'])),
            new Duration(CarbonInterval::minutes($routeStatsData['total_working_time_minutes'])),
            new Duration(CarbonInterval::minutes($routeStatsData['total_break_time_minutes'])),
            new Duration(CarbonInterval::minutes($routeStatsData['total_drive_time_minutes'])),
            Distance::fromMiles($routeStatsData['total_drive_miles']),
            new Duration(CarbonInterval::minutes($routeStatsData['average_drive_time_minutes'])),
            Distance::fromMiles($routeStatsData['average_drive_miles']),
            new Duration(CarbonInterval::minutes($routeStatsData['full_drive_time_minutes']) ?? 0),
            Distance::fromMiles($routeStatsData['full_drive_miles'] ?? 0),
        );
    }
}
