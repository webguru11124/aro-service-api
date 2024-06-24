<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriod;

class DrivingPeriodData extends AbstractTestMotiveData
{
    protected static function getRequiredEntityClass(): string
    {
        return DrivingPeriod::class;
    }

    protected static function getSignature(): array
    {
        $return = [
            'id' => random_int(10000, 99999),
            'start_time' => '2023-10-30T20:30:28-03:00',
            'end_time' => '2023-10-30T20:33:19-03:00',
            'status' => 'complete',
            'type' => 'driving',
            'annotation_status' => 1,
            'notes' => 'Assigned by Motive',
            'duration' => 171.0,
            'start_kilometers' => 21729.6403,
            'end_kilometers' => 21730.1024,
            'source' => 4,
            'driver' => (object) [
                'id' => 4354734,
                'first_name' => 'James',
                'last_name' => 'Roberts',
                'username' => null,
                'email' => 'james.roberts2@goaptive.com',
                'driver_company_id' => 'AP042100',
                'status' => 'active',
                'role' => 'driver',
            ],
            'vehicle' => (object) [
                'id' => 1257630,
                'number' => 'NEJ873',
                'year' => '2022',
                'make' => 'RAM',
                'model' => 'Promaster City',
                'vin' => 'ZFBHRFAB6N6X79522',
                'metric_units' => false,
            ],
            'origin' => '4789 E 8th St, Tulsa, OK 74112',
            'origin_lat' => 36.1505401,
            'origin_lon' => -95.9237031,
            'destination_lat' => 36.1598615,
            'destination_lon' => -95.9219937,
            'destination' => 'E Admiral Pl, Tulsa, OK 74112',
            'distance' => '0.3 mi',
            'start_hvb_state_of_charge' => null,
            'end_hvb_state_of_charge' => null,
            'start_hvb_lifetime_energy_output' => null,
            'end_hvb_lifetime_energy_output' => null,
        ];

        return $return;
    }
}
