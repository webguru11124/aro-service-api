<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DriverLocations;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationAware;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationParams;
use App\Infrastructure\Services\Motive\Client\Resources\TimeZoneAware;
use Carbon\CarbonInterface;

class SearchDriverLocationsParams extends AbstractHttpParams implements PaginationParams
{
    use PaginationAware;
    use TimeZoneAware;

    /**
     * @param CarbonInterface $date
     * @param int[] $driverIds
     */
    public function __construct(
        public readonly CarbonInterface $date,
        public readonly array $driverIds,
    ) {
        $this->timeZone = $this->date->getTimezone();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->withPagination($this->removeNullValuesAndEmptyArraysFromParamsArray([
            'driver_ids' => $this->driverIds,
        ]));
    }
}
