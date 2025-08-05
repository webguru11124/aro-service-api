<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DrivingPeriod extends AbstractEntity
{
    public function __construct(
        public readonly int $id,
        public readonly CarbonInterface $startTime,
        public readonly CarbonInterface $endTime,
        public readonly DrivingPeriodStatus $status,
        public readonly DrivingPeriodType $type,
        public readonly float $duration,
        public readonly float $startKilometers,
        public readonly float $endKilometers,
        public readonly int|null $driverId,
        public readonly int|null $vehicleId,
        public readonly float $originLat,
        public readonly float $originLon,
        public readonly float $destinationLat,
        public readonly float $destinationLon
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
            id: $apiObject->id,
            startTime: Carbon::parse($apiObject->start_time),
            endTime: Carbon::parse($apiObject->end_time),
            status: DrivingPeriodStatus::from($apiObject->status),
            type: DrivingPeriodType::from($apiObject->type),
            duration: (float) $apiObject->duration,
            startKilometers: (float) $apiObject->start_kilometers,
            endKilometers: (float) $apiObject->end_kilometers,
            driverId: $apiObject->driver?->id,
            vehicleId: $apiObject->vehicle?->id,
            originLat: (float) $apiObject->origin_lat,
            originLon: (float) $apiObject->origin_lon,
            destinationLat: (float) $apiObject->destination_lat,
            destinationLon: (float) $apiObject->destination_lon
        );
    }
}
