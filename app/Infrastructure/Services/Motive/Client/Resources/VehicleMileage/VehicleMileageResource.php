<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\Params\SearchVehicleMileageParams;
use Illuminate\Support\Collection;

class VehicleMileageResource extends AbstractResource
{
    private const URL_PATH_SEARCH = 'v1/ifta/summary';

    /**
     * @param SearchVehicleMileageParams $params
     *
     * @return Collection<int, VehicleMileage>
     */
    public function search(SearchVehicleMileageParams $params): Collection
    {
        $mapCallback = function (object $object) {
            return Collection::make(array_map(
                fn (object $vehicleMileageObject) => VehicleMileage::fromApiObject($vehicleMileageObject->ifta_trip),
                $object->ifta_trips
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
