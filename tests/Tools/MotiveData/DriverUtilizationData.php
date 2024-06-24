<?php

declare(strict_types=1);

namespace Tests\Tools\MotiveData;

use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\DriverUtilization;

class DriverUtilizationData extends AbstractTestMotiveData
{
    protected static function getRequiredEntityClass(): string
    {
        return DriverUtilization::class;
    }

    protected static function getSignature(): array
    {
        $return = [
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
            'utilization' => 20.8,
            'idle_time' => 1424,
            'idle_fuel' => 2.6,
            'driving_time' => 6641,
            'driving_fuel' => 15.5,
        ];

        return $return;
    }
}
