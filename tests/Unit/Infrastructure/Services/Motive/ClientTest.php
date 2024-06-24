<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive;

use App\Infrastructure\Services\Motive\Client\Client;
use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocationsResource;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\DriverUtilizationResource;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodsResource;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UsersResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocationsResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\VehicleMileageResource;
use Tests\TestCase;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client(\Mockery::mock(HttpClient::class));
    }

    /**
     * @test
     */
    public function it_returns_users_resource(): void
    {
        $resource = $this->client->users();
        $this->assertInstanceOf(UsersResource::class, $resource);
    }

    /**
     * @test
     */
    public function it_returns_driving_periods_resource(): void
    {
        $resource = $this->client->drivingPeriods();
        $this->assertInstanceOf(DrivingPeriodsResource::class, $resource);
    }

    /**
     * @test
     */
    public function it_returns_driver_utilization_resource(): void
    {
        $resource = $this->client->driverUtilization();
        $this->assertInstanceOf(DriverUtilizationResource::class, $resource);
    }

    /**
     * @test
     */
    public function it_returns_vehicle_mileage_resource(): void
    {
        $resource = $this->client->vehicleMileage();
        $this->assertInstanceOf(VehicleMileageResource::class, $resource);
    }

    /**
     * @test
     */
    public function it_returns_vehicle_locations_resource(): void
    {
        $resource = $this->client->vehicleLocations();
        $this->assertInstanceOf(VehicleLocationsResource::class, $resource);
    }

    /**
     * @test
     */
    public function it_returns_driver_locations_resource(): void
    {
        $resource = $this->client->driverLocations();
        $this->assertInstanceOf(DriverLocationsResource::class, $resource);
    }
}
