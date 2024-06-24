<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchedulingStateSeeder extends Seeder
{
    private const SCHEMA = 'field_operations';

    public static function getSchedulingStatesDataMock(): array
    {
        return [
            'id' => [1],
            'as_of_date' => ['2024-03-06'],
            'office_id' => [1],
            'pending_services' => [json_encode([])],
            'stats' => [json_encode([
                'routes_count' => 3,
                'pending_services_count' => 100,
                'total_assigned_services' => 68,
                'scheduled_services_count' => 15,
                'capacity_after_scheduling' => 0,
                'capacity_before_scheduling' => 32,
                'total_appointments_before_scheduling' => 5,
            ])],
            'created_at' => ['2024-03-06 12:00:00'],
            'updated_at' => ['2024-03-06 12:00:00'],
            'deleted_at' => [null],
        ];
    }

    public function run(): void
    {
        for ($i = 0; $i < count(self::getSchedulingStatesDataMock()['id']); $i++) {
            DB::table(self::SCHEMA . '.scheduling_states')->insert([
                'id' => self::getSchedulingStatesDataMock()['id'][$i],
                'as_of_date' => self::getSchedulingStatesDataMock()['as_of_date'][$i],
                'office_id' => self::getSchedulingStatesDataMock()['office_id'][$i],
                'pending_services' => self::getSchedulingStatesDataMock()['pending_services'][$i],
                'created_at' => self::getSchedulingStatesDataMock()['created_at'][$i],
                'updated_at' => self::getSchedulingStatesDataMock()['updated_at'][$i],
                'deleted_at' => self::getSchedulingStatesDataMock()['deleted_at'][$i],
            ]);
        }
    }
}
