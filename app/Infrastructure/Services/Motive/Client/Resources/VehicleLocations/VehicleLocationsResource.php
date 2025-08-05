<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use Illuminate\Support\Collection;

class VehicleLocationsResource extends AbstractResource
{
    private const URL_PATH_SEARCH = 'v2/vehicle_locations';

    /**
     * @param SearchVehicleLocationsParams $params
     *
     * @return Collection<int, VehicleLocation>
     */
    public function search(SearchVehicleLocationsParams $params): Collection
    {
        $mapCallback = function (object $object) {
            return Collection::make(array_map(
                fn (object $vehicleLocationObject) => VehicleLocation::fromApiObject($vehicleLocationObject->vehicle),
                $object->vehicles
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
