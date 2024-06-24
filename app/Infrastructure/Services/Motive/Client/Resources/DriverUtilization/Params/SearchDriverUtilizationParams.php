<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\Params;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationAware;
use App\Infrastructure\Services\Motive\Client\Resources\TimeZoneAware;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationParams;
use Carbon\CarbonInterface;

class SearchDriverUtilizationParams extends AbstractHttpParams implements PaginationParams
{
    use PaginationAware;
    use TimeZoneAware;

    /**
     * @param CarbonInterface $startDate
     * @param CarbonInterface $endDate
     * @param int[] $driverIds
     */
    public function __construct(
        public readonly CarbonInterface $startDate,
        public readonly CarbonInterface $endDate,
        public readonly array $driverIds,
    ) {
        $this->timeZone = $this->startDate->getTimezone();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->withPagination($this->removeNullValuesAndEmptyArraysFromParamsArray([
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'driver_ids' => $this->driverIds,
        ]));
    }
}
