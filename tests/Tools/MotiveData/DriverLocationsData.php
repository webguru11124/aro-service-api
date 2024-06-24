<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocation;

class DriverLocationsData extends AbstractTestMotiveData
{
    protected static function getRequiredEntityClass(): string
    {
        return DriverLocation::class;
    }

    protected static function getSignature(): array
    {
        return [
            'user' => (object) [
                'id' => 4354734,
                'first_name' => 'James',
                'last_name' => 'Roberts',
                'username' => null,
                'email' => 'james.roberts2@goaptive.com',
                'driver_company_id' => 'AP042100',
                'status' => 'active',
                'role' => 'driver',
                'current_location' => (object) [
                    'lat' => 37.7749,
                    'lon' => -122.4194,
                    'description' => 'San Francisco, CA, USA',
                    'located_at' => '2024-08-25T00:00:00Z',
                ],
                'current_vehicle' => (object) [
                    'id' => 23445435,
                    'number' => '112233',
                    'year' => 2019,
                    'make' => 'Ford',
                    'model' => 'F-150',
                    'vin' => '1FTEW1E23484745',
                ],
            ],
        ];
    }
}
