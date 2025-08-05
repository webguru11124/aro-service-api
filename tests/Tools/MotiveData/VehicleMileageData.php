<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\VehicleMileage;

class VehicleMileageData extends AbstractTestMotiveData
{
    protected static function getRequiredEntityClass(): string
    {
        return VehicleMileage::class;
    }

    protected static function getSignature(): array
    {
        $return = [
            'jurisdiction' => 'CA',
            'vehicle' => (object) [
                'id' => 1419215,
                'number' => '00219G2',
                'year' => '2017',
                'make' => 'NISSAN',
                'model' => 'FRONTIER',
                'vin' => '1N6BD0CT2HN731558',
                'metric_units' => false,
            ],
            'distance' => 50.0,
            'time_zone' => 'Central Time (US & Canada)',
        ];

        return $return;
    }
}
