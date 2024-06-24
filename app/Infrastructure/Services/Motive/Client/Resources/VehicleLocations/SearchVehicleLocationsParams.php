<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationAware;
use App\Infrastructure\Services\Motive\Client\Resources\TimeZoneAware;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationParams;
use Carbon\CarbonInterface;

class SearchVehicleLocationsParams extends AbstractHttpParams implements PaginationParams
{
    use PaginationAware;
    use TimeZoneAware;

    /**
     * @param CarbonInterface $date
     * @param int[] $vehicleIds
     * @param VehicleStatusType $vehicleStatusType
     */
    public function __construct(
        public readonly CarbonInterface $date,
        public readonly array $vehicleIds,
        public readonly VehicleStatusType $vehicleStatusType = VehicleStatusType::ACTIVE,
    ) {
        $this->timeZone = $this->date->getTimezone();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->withPagination($this->removeNullValuesAndEmptyArraysFromParamsArray([
            'status' => $this->vehicleStatusType->value,
            'vehicle_ids' => $this->vehicleIds,
        ]));
    }
}
