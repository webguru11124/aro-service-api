<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;

class VehicleMileage extends AbstractEntity
{
    public function __construct(
        public readonly int $vehicleId,
        public readonly float $distance
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
            vehicleId: $apiObject->vehicle->id,
            distance: (float) $apiObject->distance,
        );
    }
}
