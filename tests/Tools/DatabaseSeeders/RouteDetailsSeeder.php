<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tests\Traits\RouteStatsData;

class RouteDetailsSeeder extends Seeder
{
    use RouteStatsData;

    public const ROUTE_DETAILS_DATA = [
        'optimization_state_id' => [10000, 10000, 10000, 10000, 10000],
        'route_id' => [4497004, 4497710, 4498320, 4496140, 4498390],
        'stats' => [self::ROUTE_STATS[4497004], self::ROUTE_STATS[4497710], self::ROUTE_STATS[4498320], self::ROUTE_STATS[4496140], self::ROUTE_STATS[4498390]],
        'schedule' => [
            '[{"location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "description": "Start", "work_event_type": "Start Location", "scheduled_time_window": {"end": "2024-03-11 07:30:00", "start": "2024-03-11 07:30:00"}}]',
            '[{"location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "description": "Office meeting", "work_event_type": "Meeting", "expected_time_window": {"end": "2024-03-11 08:45:00", "start": "2024-03-11 07:30:00"}, "scheduled_time_window": {"end": "2024-03-11 08:45:00", "start": "2024-03-11 07:30:00"}}]',
            '[{"location": {"lat": 30.33363, "lon": -97.714622}, "priority": 25, "is_locked": 0, "description": "Pro Plus", "appointment_id": 27278857, "setup_duration": 3, "work_event_type": "Appointment", "maximum_duration": null, "minimum_duration": null, "service_duration": 20, "expected_time_window": {"end": "2024-03-11 23:59:59", "start": "2024-03-11 00:00:00"}, "scheduled_time_window": {"end": "2024-03-11 09:11:54", "start": "2024-03-11 08:48:54"}}]',
            '[{"location": {"lat": 30.365887, "lon": -97.673866}, "priority": 25, "is_locked": 0, "description": "Basic", "appointment_id": 27278845, "setup_duration": 3, "work_event_type": "Appointment", "maximum_duration": null, "minimum_duration": null, "service_duration": 20, "expected_time_window": {"end": "2024-03-11 23:59:59", "start": "2024-03-11 00:00:00"}, "scheduled_time_window": {"end": "2024-03-11 09:42:41", "start": "2024-03-11 09:19:41"}}]',
            '[{"description": "15 Min Break", "work_event_type": "Break", "scheduled_time_window": {"end": "2024-03-11 09:57:41", "start": "2024-03-11 09:42:41"}}]',
        ],
        'service_pro' => [
            '{"id": 530772, "name": "ARO QA7", "workday_id": "", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}',
            '{"id": 16515, "name": "Eusebio Alvarez", "workday_id": "AP004002", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}',
            '{"id": 7907, "name": "Maurice Allison", "workday_id": "AP003996", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}',
            '{"id": 74819, "name": "#Reschedule Route#", "workday_id": "RESCHEDULE_ROUTE", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}',
            '{"id": 530772, "name": "ARO QA7", "workday_id": "", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}',
        ],
        'details' => [
            '{"end_at": "2024-03-11 14:06:21", "capacity": 10, "start_at": "2024-03-11 07:30:00", "route_type": "Short Route", "end_location": {"lat": 30.351, "lon": -97.709}, "start_location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "optimization_score": 0.71}',
            '{"end_at": "2024-03-11 18:00:45", "capacity": 17, "start_at": "2024-03-11 07:50:00", "route_type": "Extended Route", "end_location": {"lat": 30.547, "lon": -97.863}, "start_location": {"lat": 30.547, "lon": -97.863}, "optimization_score": 0.85}',
            '{"end_at": "2024-03-11 17:00:03", "capacity": 15, "start_at": "2024-03-11 07:30:00", "route_type": "Regular Route", "end_location": {"lat": 30.449, "lon": -97.775}, "start_location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "optimization_score": 0.9}',
            '{"end_at": "2024-03-11 18:30:00", "capacity": 22, "start_at": "2024-03-11 08:00:00", "route_type": "Regular Route", "end_location": {"lat": 30.3513, "lon": -97.7094}, "start_location": {"lat": 30.3513, "lon": -97.7094}, "optimization_score": 0}',
            '{"end_at": "2024-03-11 18:30:00", "capacity": 9, "start_at": "2024-03-11 08:00:00", "route_type": "Short Route", "end_location": {"lat": 30.351, "lon": -97.709}, "start_location": {"lat": 30.351, "lon": -97.709}, "optimization_score": 0}',
        ],
        'metrics' => [
            '{"total_drive_time": {"name": "total_drive_time", "score": 5, "title": "Total Drive Time", "value": 84, "weight": 0.1}, "total_drive_miles": {"name": "total_drive_miles", "score": 5, "title": "Total Drive Miles", "value": 37.66, "weight": 0.05}, "total_working_hours": {"name": "total_working_hours", "score": 4.98, "title": "Total Working Hours", "value": 7.966666666666667, "weight": 0.2}, "total_weighted_services": {"name": "total_weighted_services", "score": 5, "title": "Total Weighted Services", "value": 14, "weight": 0.25}, "average_time_between_services": {"name": "average_time_between_services", "score": 3.08, "title": "Average Time Between Services", "value": 11.076923076923077, "weight": 0.075}, "average_miles_between_services": {"name": "average_miles_between_services", "score": 5, "title": "Average Miles Between Services", "value": 2.69, "weight": 0.075}, "average_weighted_services_per_hour": {"name": "average_weighted_services_per_hour", "score": 3.6, "title": "Average Weighted Services Per Hour", "value": 1.8, "weight": 0.25}}',
            '{"total_drive_time": {"name": "total_drive_time", "score": 5, "title": "Total Drive Time", "value": 116, "weight": 0.1}, "total_drive_miles": {"name": "total_drive_miles", "score": 5, "title": "Total Drive Miles", "value": 52.49, "weight": 0.05}, "total_working_hours": {"name": "total_working_hours", "score": 3.92, "title": "Total Working Hours", "value": 9.733333333333333, "weight": 0.2}, "total_weighted_services": {"name": "total_weighted_services", "score": 5, "title": "Total Weighted Services", "value": 17, "weight": 0.25}, "average_time_between_services": {"name": "average_time_between_services", "score": 3.13, "title": "Average Time Between Services", "value": 11, "weight": 0.075}, "average_miles_between_services": {"name": "average_miles_between_services", "score": 5, "title": "Average Miles Between Services", "value": 3.09, "weight": 0.075}, "average_weighted_services_per_hour": {"name": "average_weighted_services_per_hour", "score": 3.4, "title": "Average Weighted Services Per Hour", "value": 1.7, "weight": 0.25}}',
            '{"total_drive_time": {"name": "total_drive_time", "score": 5, "title": "Total Drive Time", "value": 42, "weight": 0.1}, "total_drive_miles": {"name": "total_drive_miles", "score": 5, "title": "Total Drive Miles", "value": 16.49, "weight": 0.05}, "total_working_hours": {"name": "total_working_hours", "score": 3.22, "title": "Total Working Hours", "value": 5.15, "weight": 0.2}, "total_weighted_services": {"name": "total_weighted_services", "score": 3.21, "title": "Total Weighted Services", "value": 9, "weight": 0.25}, "average_time_between_services": {"name": "average_time_between_services", "score": 2.03, "title": "Average Time Between Services", "value": 12.75, "weight": 0.075}, "average_miles_between_services": {"name": "average_miles_between_services", "score": 5, "title": "Average Miles Between Services", "value": 2.06, "weight": 0.075}, "average_weighted_services_per_hour": {"name": "average_weighted_services_per_hour", "score": 3.4, "title": "Average Weighted Services Per Hour", "value": 1.7, "weight": 0.25}}',
            '{"total_drive_time": {"name": "total_drive_time", "score": 5, "title": "Total Drive Time", "value": 0, "weight": 0.1}, "total_drive_miles": {"name": "total_drive_miles", "score": 5, "title": "Total Drive Miles", "value": 0, "weight": 0.05}, "total_working_hours": {"name": "total_working_hours", "score": 0, "title": "Total Working Hours", "value": 0, "weight": 0.2}, "total_weighted_services": {"name": "total_weighted_services", "score": 0, "title": "Total Weighted Services", "value": 0, "weight": 0.25}, "average_time_between_services": {"name": "average_time_between_services", "score": 5, "title": "Average Time Between Services", "value": 0, "weight": 0.075}, "average_miles_between_services": {"name": "average_miles_between_services", "score": 5, "title": "Average Miles Between Services", "value": 0, "weight": 0.075}, "average_weighted_services_per_hour": {"name": "average_weighted_services_per_hour", "score": 0, "title": "Average Weighted Services Per Hour", "value": 0, "weight": 0.25}}',
            '{"total_drive_time": {"name": "total_drive_time", "score": 5, "title": "Total Drive Time", "value": 28, "weight": 0.1}, "total_drive_miles": {"name": "total_drive_miles", "score": 5, "title": "Total Drive Miles", "value": 14.63, "weight": 0.05}, "total_working_hours": {"name": "total_working_hours", "score": 1.7, "title": "Total Working Hours", "value": 2.716666666666667, "weight": 0.2}, "total_weighted_services": {"name": "total_weighted_services", "score": 1.79, "title": "Total Weighted Services", "value": 5, "weight": 0.25}, "average_time_between_services": {"name": "average_time_between_services", "score": 3.28, "title": "Average Time Between Services", "value": 10.75, "weight": 0.075}, "average_miles_between_services": {"name": "average_miles_between_services", "score": 5, "title": "Average Miles Between Services", "value": 3.66, "weight": 0.075}, "average_weighted_services_per_hour": {"name": "average_weighted_services_per_hour", "score": 3.6, "title": "Average Weighted Services Per Hour", "value": 1.8, "weight": 0.25}}',
        ],
    ];

    public function run(): void
    {
        for ($i = 0; $i < count(self::ROUTE_DETAILS_DATA['route_id']); $i++) {
            DB::table(PostgresDBInfo::ROUTE_DETAILS_TABLE)->insert([
                'optimization_state_id' => self::ROUTE_DETAILS_DATA['optimization_state_id'][$i],
                'route_id' => self::ROUTE_DETAILS_DATA['route_id'][$i],
                'schedule' => self::ROUTE_DETAILS_DATA['schedule'][$i],
                'service_pro' => self::ROUTE_DETAILS_DATA['service_pro'][$i],
                'stats' => self::ROUTE_DETAILS_DATA['stats'][$i],
                'details' => self::ROUTE_DETAILS_DATA['details'][$i],
                'metrics' => self::ROUTE_DETAILS_DATA['metrics'][$i],
            ]);
        }
    }
}
