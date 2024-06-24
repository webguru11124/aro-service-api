<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive;

use App\Domain\Contracts\Services\VehicleTrackingDataService;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocation;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\SearchDriverLocationsParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\SearchVehicleLocationsParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MotiveVehicleTrackingDataService extends AbstractMotiveDataService implements VehicleTrackingDataService
{
    private CarbonInterface $date;

    /**
     * @param string[] $userIds
     * @param CarbonInterface $date
     *
     * @return Collection<string, RouteTrackingData>
     */
    public function get(array $userIds, CarbonInterface $date): Collection
    {
        $this->date = $date->clone()->startOfDay();

        try {
            $driverIds = array_flip($this->preloadDrivers($userIds));
            $driverLocations = $this->getDriverLocations(array_keys($driverIds));
            /** @var int[] $vehicleIds */
            $vehicleIds = $driverLocations->map(
                fn (DriverLocation $driverLocation) => $driverLocation->vehicleId
            )->filter()->toArray();

            $vehicleLocations = $this->getVehicleLocations($vehicleIds);
        } catch (MotiveClientException $e) {
            Log::error(
                __('messages.service_stats.error_getting_tracking_data', ['date' => $this->date->toDateString()]),
                ['exception' => $e->getMessage()]
            );

            return collect();
        }

        return $driverLocations->mapWithKeys(
            function (DriverLocation $driverLocation) use ($vehicleLocations, $driverIds) {
                $userId = $driverIds[$driverLocation->driverId];
                $vehicleLocation = !empty($driverLocation->vehicleId)
                    ? $vehicleLocations->get($driverLocation->vehicleId)
                    : null;
                $routeTrackingData = new RouteTrackingData(
                    id: $userId,
                    driverLocation: $driverLocation->location,
                    driverLocatedAt: $driverLocation->lastSeenAt,
                    vehicleLocation: $vehicleLocation?->location,
                    vehicleLocatedAt: $vehicleLocation?->lastSeenAt,
                    vehicleSpeed: $vehicleLocation?->speed,
                );

                return [$userId => $routeTrackingData];
            }
        );
    }

    /**
     * @param int[] $vehicleIds
     *
     * @return Collection<VehicleLocation>
     */
    private function getVehicleLocations(array $vehicleIds): Collection
    {
        if (empty($vehicleIds)) {
            return collect();
        }

        return $this->client->vehicleLocations()
            ->search(new SearchVehicleLocationsParams(
                date: $this->date,
                vehicleIds: $vehicleIds,
            ))->keyBy('vehicleId');
    }

    /**
     * @param int[] $driverIds
     *
     * @return Collection<DriverLocation>
     */
    private function getDriverLocations(array $driverIds): Collection
    {
        if (empty($driverIds)) {
            return collect();
        }

        return $this->client->driverLocations()
            ->search(new SearchDriverLocationsParams(
                date: $this->date,
                driverIds: $driverIds,
            ))->keyBy('driverId');
    }
}
