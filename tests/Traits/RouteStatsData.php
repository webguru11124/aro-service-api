<?php

declare(strict_types=1);

namespace Tests\Traits;

trait RouteStatsData
{
    public const ROUTE_STATS = [
        4497004 => '{"total_regular": 20, "total_initials": 1, "total_reservice": 0, "total_drive_miles": 55.75, "total_appointments": 21, "average_drive_miles": 1.49, "total_weighted_services": 22, "total_break_time_minutes": 60, "total_drive_time_minutes": 115, "average_drive_time_minutes": 3, "total_service_time_minutes": 512, "total_working_time_minutes": 590, "full_drive_time_minutes": 115, "full_drive_miles": 55.75}',
        4497710 => '{"total_regular": 0, "total_initials": 0, "total_reservice": 0, "total_drive_miles": 0, "total_appointments": 0, "average_drive_miles": 0, "total_weighted_services": 0, "total_break_time_minutes": 0, "total_drive_time_minutes": 0, "average_drive_time_minutes": 0, "total_service_time_minutes": 0, "total_working_time_minutes": 0, "full_drive_time_minutes": 0, "full_drive_miles": 0}',
        4498320 => '{"total_regular": 0, "total_initials": 0, "total_reservice": 0, "total_drive_miles": 0, "total_appointments": 0, "average_drive_miles": 0, "total_weighted_services": 0, "total_break_time_minutes": 0, "total_drive_time_minutes": 0, "average_drive_time_minutes": 0, "total_service_time_minutes": 0, "total_working_time_minutes": 0, "full_drive_time_minutes": 0, "full_drive_miles": 0}',
        4496140 => '{"total_regular": 0, "total_initials": 0, "total_reservice": 0, "total_drive_miles": 0, "total_appointments": 0, "average_drive_miles": 0, "total_weighted_services": 0, "total_break_time_minutes": 0, "total_drive_time_minutes": 0, "average_drive_time_minutes": 0, "total_service_time_minutes": 0, "total_working_time_minutes": 0, "full_drive_time_minutes": 0, "full_drive_miles": 0}',
        4498390 => '{"total_regular": 0, "total_initials": 0, "total_reservice": 0, "total_drive_miles": 0, "total_appointments": 0, "average_drive_miles": 0, "total_weighted_services": 0, "total_break_time_minutes": 0, "total_drive_time_minutes": 0, "average_drive_time_minutes": 0, "total_service_time_minutes": 0, "total_working_time_minutes": 0, "full_drive_time_minutes": 0, "full_drive_miles": 0}',
    ];

    public const ROUTE_ACTUAL_STATS = [
        4497004 => '{"total_drive_miles": 60.05, "average_drive_miles": 4.35, "total_drive_time_minutes": 139, "average_drive_time_minutes": 10, "total_working_time_minutes": 566, "fuel_consumption": 5.6, "route_adherence": 80.5}',
        4497710 => '{"total_drive_miles": 53.62, "average_drive_miles": 7.46, "total_drive_time_minutes": 91, "average_drive_time_minutes": 13, "total_working_time_minutes": 247, "fuel_consumption": 5.6, "route_adherence": 100.0}',
    ];

    public function getRouteStatsDataById(int $id): array
    {
        return [
            'route_stats' => json_decode(self::ROUTE_STATS[$id], true),
            'actual_stats' => !empty(self::ROUTE_ACTUAL_STATS[$id]) ? json_decode(self::ROUTE_ACTUAL_STATS[$id], true) : null,
        ];
    }
}
