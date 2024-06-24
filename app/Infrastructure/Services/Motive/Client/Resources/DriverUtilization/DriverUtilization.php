<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;

class DriverUtilization extends AbstractEntity
{
    public function __construct(
        public readonly int|null $driverId,
        public readonly int $utilization,
        public readonly int $idleTime,
        public readonly float $idleFuel,
        public readonly int $drivingTime,
        public readonly float $drivingFuel,
    ) {
    }

    /**
     * @param object $apiObject
     *
     * @return self
     */
    public static function fromApiObject(object $apiObject): self
    {
        return new self(
            driverId: $apiObject->driver->id,
            utilization: (int) $apiObject->utilization,
            idleTime: (int) $apiObject->idle_time,
            idleFuel: (float) $apiObject->idle_fuel,
            drivingTime: (int) $apiObject->driving_time,
            drivingFuel: (float) $apiObject->driving_fuel
        );
    }
}
