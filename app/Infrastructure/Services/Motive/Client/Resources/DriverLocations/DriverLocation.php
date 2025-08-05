<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DriverLocations;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DriverLocation extends AbstractEntity
{
    public function __construct(
        public readonly int $driverId,
        public readonly int|null $vehicleId,
        public readonly CarbonInterface|null $lastSeenAt,
        public readonly Coordinate|null $location,
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
            driverId: $apiObject->id,
            vehicleId: $apiObject->current_vehicle?->id,
            lastSeenAt: !empty($apiObject->current_location)
                ? Carbon::parse($apiObject->current_location->located_at)
                : null,
            location: !empty($apiObject->current_location)
                ? new Coordinate((float) $apiObject->current_location->lat, (float) $apiObject->current_location->lon)
                : null,
        );
    }
}
