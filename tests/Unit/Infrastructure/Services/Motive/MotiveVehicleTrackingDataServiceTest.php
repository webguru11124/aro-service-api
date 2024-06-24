<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use App\Infrastructure\Services\Motive\Client\Client;
use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocation;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocationsResource;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\FindUserParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\User;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UsersResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocation;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocationsResource;
use App\Infrastructure\Services\Motive\MotiveRouteDrivingDataService;
use App\Infrastructure\Services\Motive\MotiveVehicleTrackingDataService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\FleetRouteFactory;
use Tests\Tools\MotiveData\UserData;
use Tests\Traits\MotiveClientMockBuilderAware;

class MotiveVehicleTrackingDataServiceTest extends TestCase
{
    use MotiveClientMockBuilderAware;

    /**
     * @test
     */
    public function it_returns_driver_locations(): void
    {
        /** @var FleetRoute $route */
        $route = FleetRouteFactory::make();
        $date = $route->getStartAt()->clone();
        $vehiculeId = 2;

        $workdayId = $route->getServicePro()->getWorkdayId();
        $userIds = [$workdayId];

        /** @var User $motiveUser */
        $motiveUser = UserData::getTestData(1, ['id' => 4354734, 'driver_company_id' => $workdayId])->first();

        $clientMock = $this->getMotiveClientMockBuilder()
            ->resource('users', UsersResource::class)
            ->cached()
            ->method('search')
            ->willReturn(collect([$motiveUser]))
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('users', UsersResource::class)
            ->cached()
            ->preload(function (User $user, $method, FindUserParams $params) use ($workdayId) {
                return $user->companyId === $workdayId && $method === 'find' && $params->driverCompanyId === $workdayId;
            });
        $mockDriverLocation = new DriverLocation(
            driverId: $motiveUser->id,
            vehicleId: $vehiculeId,
            lastSeenAt: Carbon::now(),
            location: new Coordinate(1.0, 1.0),
        );
        $mockVehicleLocation = new VehicleLocation(
            vehicleId: $vehiculeId,
            lastSeenAt: Carbon::now(),
            speed: 10.0,
            location: new Coordinate(2.0, 4.0),
        );
        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('driverLocations', DriverLocationsResource::class)
            ->method('search')
            ->withArgs(function ($params) use ($motiveUser, $date) {
                return $params->driverIds[0] === $motiveUser->id && $params->date->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString();
            })
            ->willReturn(collect([$mockDriverLocation]))
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('vehicleLocations', VehicleLocationsResource::class)
            ->method('search')
            ->withArgs(function ($params) use ($vehiculeId, $date, $motiveUser) {
                return $params->vehicleIds[$motiveUser->id] === $vehiculeId && $params->date->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString();
            })
            ->willReturn(collect([$mockVehicleLocation]))
            ->mock();

        $service = new MotiveVehicleTrackingDataService($clientMock);
        $results = $service->get($userIds, $date);

        $this->assertCount(1, $results);

        /** @var RouteTrackingData $result */
        $result = $results->first();

        $this->assertEquals($result->getId(), $motiveUser->companyId);
        $this->assertEquals($result->getDriverLocation(), $mockDriverLocation->location);
        $this->assertEquals($result->getDriverLocatedAt()->toDateString(), $date->toDateString());
        $this->assertEquals($result->getVehicleLocatedAt()->toDateString(), $date->toDateString());
        $this->assertEquals($result->getVehicleLocation(), $mockVehicleLocation->location);
        $this->assertEquals($result->getVehicleSpeed(), $mockVehicleLocation->speed);
    }

    /**
     * @test
     */
    public function it_returns_empty_when_no_driver_locations_found(): void
    {
        /** @var FleetRoute $route */
        $route = FleetRouteFactory::make();
        $date = $route->getStartAt()->clone();

        $workdayId = $route->getServicePro()->getWorkdayId();
        $userIds = [$workdayId];

        /** @var User $motiveUser */
        $motiveUser = UserData::getTestData(1, ['id' => 4354734, 'driver_company_id' => $workdayId])->first();

        $clientMock = $this->getMotiveClientMockBuilder()
            ->resource('users', UsersResource::class)
            ->cached()
            ->method('search')
            ->willReturn(collect([$motiveUser]))
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('users', UsersResource::class)
            ->cached()
            ->preload(function (User $user, $method, FindUserParams $params) use ($workdayId) {
                return $user->companyId === $workdayId && $method === 'find' && $params->driverCompanyId === $workdayId;
            });
        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('driverLocations', DriverLocationsResource::class)
            ->method('search')
            ->withArgs(function ($params) use ($motiveUser, $date) {
                return $params->driverIds[0] === $motiveUser->id && $params->date->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString();
            })
            ->willReturn(collect([]))
            ->mock();

        $service = new MotiveVehicleTrackingDataService($clientMock);
        $results = $service->get($userIds, $date);

        $this->assertCount(0, $results);
    }

    /**
     * @test
     */
    public function it_returns_data_when_no_vehicule_locations_found(): void
    {
        /** @var FleetRoute $route */
        $route = FleetRouteFactory::make();
        $date = $route->getStartAt()->clone();
        $vehiculeId = 2;

        $workdayId = $route->getServicePro()->getWorkdayId();
        $userIds = [$workdayId];

        /** @var User $motiveUser */
        $motiveUser = UserData::getTestData(1, ['id' => 4354734, 'driver_company_id' => $workdayId])->first();

        $clientMock = $this->getMotiveClientMockBuilder()
            ->resource('users', UsersResource::class)
            ->cached()
            ->method('search')
            ->willReturn(collect([$motiveUser]))
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('users', UsersResource::class)
            ->cached()
            ->preload(function (User $user, $method, FindUserParams $params) use ($workdayId) {
                return $user->companyId === $workdayId && $method === 'find' && $params->driverCompanyId === $workdayId;
            });
        $mockDriverLocation = new DriverLocation(
            driverId: $motiveUser->id,
            vehicleId: $vehiculeId,
            lastSeenAt: Carbon::now(),
            location: new Coordinate(1.0, 1.0),
        );
        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('driverLocations', DriverLocationsResource::class)
            ->method('search')
            ->withArgs(function ($params) use ($motiveUser, $date) {
                return $params->driverIds[0] === $motiveUser->id && $params->date->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString();
            })
            ->willReturn(collect([$mockDriverLocation]))
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('vehicleLocations', VehicleLocationsResource::class)
            ->method('search')
            ->withArgs(function ($params) use ($vehiculeId, $date, $motiveUser) {
                return $params->vehicleIds[$motiveUser->id] === $vehiculeId && $params->date->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString();
            })
            ->willReturn(collect([]))
            ->mock();

        $service = new MotiveVehicleTrackingDataService($clientMock);
        $results = $service->get($userIds, $date);

        $this->assertCount(1, $results);

        /** @var RouteTrackingData $result */
        $result = $results->first();

        $this->assertEquals($result->getId(), $motiveUser->companyId);
        $this->assertEquals($result->getDriverLocation(), $mockDriverLocation->location);
        $this->assertEquals($result->getDriverLocatedAt()->toDateString(), $date->toDateString());
        $this->assertEquals($result->getVehicleLocatedAt(), null);
        $this->assertEquals($result->getVehicleLocation(), null);
        $this->assertEquals($result->getVehicleSpeed(), null);
    }

    /**
     * @test
     * ::MotiveClientException
     */
    public function it_correctly_logs_and_handles_motive_exception(): void
    {
        /** @var FleetRoute $route */
        $route = FleetRouteFactory::make();
        $date = $route->getStartAt()->clone();
        $mockExceptionMessage = 'Mock Motive Client Exception';

        $workdayId = $route->getServicePro()->getWorkdayId();
        $userIds = [$workdayId];

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('users')
            ->once()
            ->andThrow(new MotiveClientException($mockExceptionMessage));

        Log::shouldReceive('error')->once();

        $service = new MotiveRouteDrivingDataService($mockClient);
        $results = $service->get($userIds, $date);

        $this->assertCount(0, $results);
    }
}
