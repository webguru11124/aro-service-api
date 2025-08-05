<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocation;

class VehicleLocationsData extends AbstractTestMotiveData
{
    protected static function getRequiredEntityClass(): string
    {
        return VehicleLocation::class;
    }

    protected static function getSignature(): array
    {
        return [
            'vehicle' => (object) [
                'id' => 1420493,
                'number' => '00218G2',
                'year' => '2017',
                'make' => 'NISSAN',
                'model' => 'FRONTIER',
                'vin' => '1N6BD0CT4HN732095',
                'current_location' => (object) [
                    'lat' => 33.8837989,
                    'lon' => -117.7395445,
                    'located_at' => '2023-12-06T09:22:24Z',
                    'bearing' => 0.0,
                    'engine_hours' => 3339.3573522,
                    'id' => 'abcde95c-74f1-43d2-88ec-ba4128037943',
                    'type' => 'breadcrumb',
                    'description' => 'Yorba Linda, CA',
                    'speed' => 14.5,
                    'odometer' => 50081.881229,
                    'battery_voltage' => null,
                    'fuel' => null,
                    'fuel_primary_remaining_percentage' => null,
                    'fuel_secondary_remaining_percentage' => null,
                ],
                'current_driver' => null,
            ],
        ];
    }
}
