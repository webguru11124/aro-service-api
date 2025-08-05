<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DriverLocations;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use Illuminate\Support\Collection;

class DriverLocationsResource extends AbstractResource
{
    private const URL_PATH_SEARCH = 'v1/driver_locations';

    /**
     * @param SearchDriverLocationsParams $params
     *
     * @return Collection<int, DriverLocation>
     */
    public function search(SearchDriverLocationsParams $params): Collection
    {
        $mapCallback = function (object $object) {
            return Collection::make(array_map(
                fn (object $driverLocationObject) => DriverLocation::fromApiObject($driverLocationObject->user),
                $object->users
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
