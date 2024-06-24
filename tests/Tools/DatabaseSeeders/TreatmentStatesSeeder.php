<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tests\Tools\TestValue;

class TreatmentStatesSeeder extends Seeder
{
    private const TRETATMENT_STATE_DATA = [
        'id' => [TestValue::TREATMENT_STATE_ID],
        'office_id' => [TestValue::OFFICE_ID],
        'stats' => [
            [
                'total_routes' => 3,
                'total_drive_miles' => 106.64,
                'total_appointments' => 40,
                'total_routes_actual' => 3,
                'appointments_per_gallon' => 9.29,
                'total_drive_miles_actual' => 69.44,
                'total_drive_time_minutes' => 242,
                'total_appointments_actual' => 3,
                'total_service_time_minutes' => 949,
                'appointments_per_gallon_actual' => 1.05,
                'total_drive_time_minutes_actual' => 156,
                'total_service_time_minutes_actual' => 105,
            ],
        ],
    ];

    public function run(): void
    {
        for ($i = 0; $i < count(self::TRETATMENT_STATE_DATA); $i++) {
            DB::table(PostgresDBInfo::TREATMENT_STATE_TABLE)->insert([
                'id' => self::TRETATMENT_STATE_DATA['id'][$i],
                'office_id' => self::TRETATMENT_STATE_DATA['office_id'][$i],
                'stats' => json_encode(self::TRETATMENT_STATE_DATA['stats'][$i]),
            ]);
        }
    }
}
