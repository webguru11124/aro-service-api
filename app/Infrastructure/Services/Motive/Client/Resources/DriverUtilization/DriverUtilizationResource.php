<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\Params\SearchDriverUtilizationParams;
use Illuminate\Support\Collection;

class DriverUtilizationResource extends AbstractResource
{
    private const URL_PATH_SEARCH = 'v2/driver_utilization';

    /**
     * @param SearchDriverUtilizationParams $params
     *
     * @return Collection<int, DriverUtilization>
     */
    public function search(SearchDriverUtilizationParams $params): Collection
    {
        $mapCallback = function (object $object) {
            return Collection::make(array_map(
                fn (object $driverUtilizationObject) => DriverUtilization::fromApiObject($driverUtilizationObject->driver_idle_rollup),
                $object->driver_idle_rollups
            ));
        };

        return $this->getWithPagination(
            endpoint: $this->getBaseUrl() . self::URL_PATH_SEARCH,
            mapCallback: $mapCallback,
            params: $params,
            headers: [self::HEADER_TIME_ZONE => $params->getTimeZone()]
        )->values();
    }
}
