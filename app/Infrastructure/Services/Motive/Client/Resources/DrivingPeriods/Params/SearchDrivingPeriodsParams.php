<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodStatus;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodType;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationAware;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationParams;
use App\Infrastructure\Services\Motive\Client\Resources\TimeZoneAware;
use Carbon\CarbonInterface;

class SearchDrivingPeriodsParams extends AbstractHttpParams implements PaginationParams
{
    use PaginationAware;
    use TimeZoneAware;

    /**
     * @param CarbonInterface $startDate
     * @param CarbonInterface $endDate
     * @param int[]|null $driverIds
     * @param int|null $vehicleId
     * @param DrivingPeriodType|null $type
     * @param DrivingPeriodStatus|null $status
     */
    public function __construct(
        public readonly CarbonInterface $startDate,
        public readonly CarbonInterface $endDate,
        public readonly array|null $driverIds = null,
        public readonly int|null $vehicleId = null,
        public readonly DrivingPeriodType|null $type = null,
        public readonly DrivingPeriodStatus|null $status = null,
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
            'vehicle_ids' => $this->vehicleId,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
        ]));
    }
}
