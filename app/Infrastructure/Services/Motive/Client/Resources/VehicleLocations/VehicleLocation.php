<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class VehicleLocation extends AbstractEntity
{
    public function __construct(
        public readonly int $vehicleId,
        public readonly CarbonInterface $lastSeenAt,
        public readonly float $speed,
        public readonly Coordinate $location,
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
            vehicleId: $apiObject->id,
            lastSeenAt: Carbon::parse($apiObject->current_location->located_at),
            speed: (float) $apiObject->current_location->speed,
            location: new Coordinate(
                (float) $apiObject->current_location->lat,
                (float) $apiObject->current_location->lon
            ),
        );
    }
}
