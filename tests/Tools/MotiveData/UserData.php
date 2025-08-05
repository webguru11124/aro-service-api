<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\Users\User;

class UserData extends AbstractTestMotiveData
{
    protected static function getRequiredEntityClass(): string
    {
        return User::class;
    }

    protected static function getSignature(): array
    {
        $return = [
            'id' => random_int(100000, 999999),
            'email' => null,
            'first_name' => 'Richard',
            'last_name' => 'Gardner',
            'phone' => null,
            'phone_ext' => null,
            'time_zone' => 'Mountain Time (US & Canada)',
            'metric_units' => false,
            'carrier_name' => 'Aptive Environmental',
            'carrier_street' => '251 West River Park Drive',
            'carrier_city' => 'Provo',
            'carrier_state' => 'UT',
            'carrier_zip' => '84604',
            'violation_alerts' => '1_hour',
            'terminal_street' => '',
            'terminal_city' => '',
            'terminal_state' => '',
            'terminal_zip' => '',
            'cycle' => 'Other',
            'exception_24_hour_restart' => false,
            'exception_8_hour_break' => false,
            'exception_wait_time' => false,
            'exception_short_haul' => false,
            'exception_ca_farm_school_bus' => false,
            'cycle2' => null,
            'exception_24_hour_restart2' => false,
            'exception_8_hour_break2' => false,
            'exception_wait_time2' => false,
            'exception_short_haul2' => false,
            'exception_ca_farm_school_bus2' => false,
            'exception_adverse_driving' => false,
            'exception_adverse_driving2' => false,
            'export_combined' => true,
            'export_recap' => true,
            'export_odometers' => true,
            'username' => null,
            'driver_company_id' => '',
            'minute_logs' => true,
            'duty_status' => 'off_duty',
            'eld_mode' => 'exempt',
            'drivers_license_number' => '',
            'drivers_license_state' => '',
            'yard_moves_enabled' => false,
            'personal_conveyance_enabled' => false,
            'manual_driving_enabled' => false,
            'mobile_last_active_at' => '2023-05-31T16:53:26Z',
            'mobile_current_sign_in_at' => null,
            'mobile_last_sign_in_at' => '2023-02-27T22:33:21Z',
            'web_last_active_at' => '2023-02-27T22:33:21Z',
            'role' => 'driver',
            'status' => 'deactivated',
            'web_current_sign_in_at' => null,
            'web_last_sign_in_at' => null,
            'external_ids' => [],
            'created_at' => '2023-02-07T22:21:45Z',
            'updated_at' => '2023-09-20T17:24:11.541616Z',
        ];

        return $return;
    }
}
