<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tests\Tools\TestValue;

class ServicedRouteDetailsSeeder extends Seeder
{
    private const SERVICED_ROUTE_DETAILS_DATA = [
        'treatment_state_id' => [TestValue::TREATMENT_STATE_ID],
        'route_id' => [4497004],
        'service_pro' => [['service_pro_id' => TestValue::EMPLOYEE_ID, 'service_pro' => 'John Doe']],
        'stats' => [
            [
                'total_drive_miles' => 39.61,
                'average_drive_miles' => 8.08,
                'total_drive_time_minutes' => 82,
                'average_drive_time_minutes' => 16,
                'total_working_time_minutes' => 480,
                'fuel_consumption' => 3.8,
                'route_adherence' => 90.0,
            ],
        ],
    ];

    public function run(): void
    {
        for ($i = 0; $i < count(self::SERVICED_ROUTE_DETAILS_DATA['treatment_state_id']); $i++) {
            DB::table(PostgresDBInfo::SERVICED_ROUTE_DETAILS_TABLE)->insert([
                'treatment_state_id' => self::SERVICED_ROUTE_DETAILS_DATA['treatment_state_id'][$i],
                'route_id' => self::SERVICED_ROUTE_DETAILS_DATA['route_id'][$i],
                'service_pro' => json_encode(self::SERVICED_ROUTE_DETAILS_DATA['service_pro'][$i]),
                'stats' => json_encode(self::SERVICED_ROUTE_DETAILS_DATA['stats'][$i]),
            ]);
        }
    }
}
