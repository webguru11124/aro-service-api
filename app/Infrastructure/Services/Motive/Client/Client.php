<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client;

use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocationsResource;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodsResource;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\DriverUtilizationResource;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UsersResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocationsResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\VehicleMileageResource;
use Psr\SimpleCache\CacheInterface;

class Client
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly CacheInterface|null $cacheClient = null
    ) {
    }

    /**
     * @return UsersResource
     */
    public function users(): UsersResource
    {
        return new UsersResource($this->httpClient, $this->cacheClient);
    }

    /**
     * @return DrivingPeriodsResource
     */
    public function drivingPeriods(): DrivingPeriodsResource
    {
        return new DrivingPeriodsResource($this->httpClient, $this->cacheClient);
    }

    /**
     * @return DriverUtilizationResource
     */
    public function driverUtilization(): DriverUtilizationResource
    {
        return new DriverUtilizationResource($this->httpClient, $this->cacheClient);
    }

    /**
     * @return VehicleMileageResource
     */
    public function vehicleMileage(): VehicleMileageResource
    {
        return new VehicleMileageResource($this->httpClient, $this->cacheClient);
    }

    /**
     * @return VehicleLocationsResource
     */
    public function vehicleLocations(): VehicleLocationsResource
    {
        return new VehicleLocationsResource($this->httpClient, $this->cacheClient);
    }

    /**
     * @return DriverLocationsResource
     */
    public function driverLocations(): DriverLocationsResource
    {
        return new DriverLocationsResource($this->httpClient, $this->cacheClient);
    }
}
