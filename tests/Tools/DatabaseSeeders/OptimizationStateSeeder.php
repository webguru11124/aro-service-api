<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptimizationStateSeeder extends Seeder
{
    /**
     * @return mixed[]
     */
    public static function getOptimizationStatesMockData(): array
    {
        return [
            'id' => [10000, 10001],
            'state' => [
                '{"engine": "Vroom", "params": {"simulation_run": false, "last_optimization_run": false, "build_planned_optimization": false}, "created_at": 1712330270, "optimization_window_end": "2024-03-11 23:59:59", "unassigned_appointments": [], "optimization_window_start": "2024-03-11 00:00:00"}',
                '{"engine": "Vroom", "params": {"simulation_run": false, "last_optimization_run": false, "build_planned_optimization": false}, "created_at": 1712330289, "optimization_window_end": "2024-03-11 23:59:59", "unassigned_appointments": [{"id": 27278788}], "optimization_window_start": "2024-03-11 00:00:00"}',
            ],
            'created_at' => ['2021-01-01 00:00:00', '2021-01-01 00:00:00'],
            'updated_at' => ['2021-01-02 00:00:00', '2021-01-02 00:00:00'],
            'deleted_at' => [null, null],
            'status' => [OptimizationStatus::PRE, OptimizationStatus::POST],
            'office' => [
                '{"office": "Demo Demo Salt Lake City Central", "office_id": 94}',
                '{"office": "Demo Demo Avenue Parker", "office_id": 95}',
            ],
            'stats' => [
                '{"total_routes": 4, "total_drive_time": 115, "services_per_hour": 2.46, "total_drive_miles": 55.75, "average_daily_working_hours": 9.85, "total_assigned_appointments": 21, "total_unassigned_appointments": 0}',
                '{"total_routes": 4, "total_drive_time": 115, "services_per_hour": 2.46, "total_drive_miles": 55.75, "average_daily_working_hours": 9.85, "total_assigned_appointments": 21, "total_unassigned_appointments": 0}',
            ],
            'as_of_date' => ['2021-01-01', '2021-01-01'],
            'previous_state_id' => [null, 10000],
            'rules' => [
                null,
                '[{"id": "MustConsiderRoadTraffic", "name": "Must Consider Road Traffic", "is_applied": false, "description": "Consider traffic estimation in optimization solution", "is_triggered": false}]',
            ],
            'metrics' => [
                null,
                '{"total_drive_time": 5, "total_drive_miles": 5, "optimization_score": 0.82, "total_working_hours": 4.04, "total_weighted_services": 4.4, "average_time_between_services": 2.75, "average_miles_between_services": 5, "average_weighted_services_per_hour": 3.47}',
            ],
            'weather_forecast' => [
                null,
                '{"wind": "W 4.65", "humidity": 94, "pressure": 1016, "condition": "Clouds", "temperature": null, "is_inclement": false}',
            ],
        ];
    }

    public function run(): void
    {
        for ($i = 0; $i < count(self::getOptimizationStatesMockData()['id']); $i++) {
            DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE)->insert([
                'id' => self::getOptimizationStatesMockData()['id'][$i],
                'state' => self::getOptimizationStatesMockData()['state'][$i],
                'created_at' => self::getOptimizationStatesMockData()['created_at'][$i],
                'updated_at' => self::getOptimizationStatesMockData()['updated_at'][$i],
                'deleted_at' => self::getOptimizationStatesMockData()['deleted_at'][$i],
                'office' => self::getOptimizationStatesMockData()['office'][$i],
                'stats' => self::getOptimizationStatesMockData()['stats'][$i],
                'status' => self::getOptimizationStatesMockData()['status'][$i],
                'rules' => self::getOptimizationStatesMockData()['rules'][$i],
                'metrics' => self::getOptimizationStatesMockData()['metrics'][$i],
                'weather_forecast' => self::getOptimizationStatesMockData()['weather_forecast'][$i],
                'as_of_date' => self::getOptimizationStatesMockData()['as_of_date'][$i],
                'previous_state_id' => self::getOptimizationStatesMockData()['previous_state_id'][$i],
            ]);
        }
    }
}
