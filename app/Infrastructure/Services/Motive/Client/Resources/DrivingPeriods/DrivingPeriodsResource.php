<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params\SearchDrivingPeriodsParams;
use Illuminate\Support\Collection;

class DrivingPeriodsResource extends AbstractResource
{
    private const URL_PATH_FIND = 'v1/driving_periods';

    /**
     * @param SearchDrivingPeriodsParams $params
     *
     * @return Collection<int, DrivingPeriod>
     */
    public function search(SearchDrivingPeriodsParams $params): Collection
    {
        $mapCallback = function (object $object) {
            return Collection::make(array_map(
                fn (object $drivingPeriodObject) => DrivingPeriod::fromApiObject($drivingPeriodObject->driving_period),
                $object->driving_periods
            ));
        };

        return $this->getWithPagination(
            endpoint: $this->getBaseUrl() . self::URL_PATH_FIND,
            mapCallback: $mapCallback,
            params: $params,
            headers: [self::HEADER_TIME_ZONE => $params->getTimeZone()]
        )->sortBy('startTime')->values();
    }
}
