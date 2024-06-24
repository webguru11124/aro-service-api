<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive;

use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use App\Infrastructure\Services\Motive\Client\Client;
use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\DriverUtilizationResource;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\Params\SearchDriverUtilizationParams;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodsResource;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params\SearchDrivingPeriodsParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\SearchUsersParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserRole;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\VehicleMileageResource;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\Params\SearchVehicleMileageParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\FindUserParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\User;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UsersResource;
use App\Infrastructure\Services\Motive\MotiveRouteDrivingDataService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\FleetRouteFactory;
use Tests\Tools\MotiveData\DriverUtilizationData;
use Tests\Tools\MotiveData\DrivingPeriodData;
use Tests\Tools\MotiveData\UserData;
use Tests\Tools\MotiveData\VehicleMileageData;
use Tests\Traits\MotiveClientMockBuilderAware;

class MotiveRouteDrivingDataServiceTest extends TestCase
{
    use MotiveClientMockBuilderAware;

    private const HISTORIC_DATA_DURATION_WEEKS = 2;

    /**
     * @test
     */
    public function it_returns_actual_route_stats(): void
    {
        /** @var FleetRoute $route */
        $route = FleetRouteFactory::make();
        $workdayId = $route->getServicePro()->getWorkdayId();
        $userIds = [$workdayId];

        $date = $route->getStartAt()->clone();

        /** @var User $motiveUser */
        $motiveUser = UserData::getTestData(1, ['id' => 4354734, 'driver_company_id' => $workdayId])->first();

        $clientMock = $this->getMotiveClientMockBuilder()
            ->resource('users', UsersResource::class)
            ->cached()
            ->method('search')
            ->withArgs(function (SearchUsersParams $params) {
                return $params->role === UserRole::DRIVER;
            })
            ->willReturn(collect([$motiveUser]))
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('users', UsersResource::class)
            ->cached()
            ->preload(function (User $user, $method, FindUserParams $params) use ($workdayId) {
                return $user->companyId === $workdayId && $method === 'find' && $params->driverCompanyId === $workdayId;
            });

        $vehicleId1 = 1419215;
        $vehicleId2 = 1419216;
        $duration = 100;
        $kilometers = 6;
        $drivingPeriodsNumber = 3;

        $drivingPeriods = DrivingPeriodData::getTestData(
            $drivingPeriodsNumber,
            [
                'duration' => $duration,
                'start_kilometers' => 10,
                'end_kilometers' => 12,
                'vehicle' => (object) ['id' => $vehicleId1],
            ],
            [
                'duration' => $duration,
                'start_kilometers' => 12,
                'end_kilometers' => 14,
                'vehicle' => (object) ['id' => $vehicleId1],
            ],
            [
                'duration' => $duration,
                'start_kilometers' => 20,
                'end_kilometers' => 22,
                'vehicle' => (object) ['id' => $vehicleId2],
            ],
        );

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('drivingPeriods', DrivingPeriodsResource::class)
            ->cached()
            ->method('search')
            ->withArgs(function (SearchDrivingPeriodsParams $params) use ($motiveUser, $date) {
                return $params->startDate->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString()
                    && $params->endDate->toDateTimeString() === $date->clone()->endOfDay()->toDateTimeString()
                    && $params->driverIds === [$motiveUser->id];
            })
            ->willReturn($drivingPeriods)
            ->times(1)
            ->mock();

        $drivingFuelConsumption = 5.7;
        $idleFuelConsumption = 4.3;
        $driverUtilization = DriverUtilizationData::getTestData(
            1,
            [
                'driving_fuel' => $drivingFuelConsumption,
                'idle_fuel' => $idleFuelConsumption,
            ]
        );

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('driverUtilization', DriverUtilizationResource::class)
            ->cached()
            ->method('search')
            ->withArgs(function (SearchDriverUtilizationParams $params) use ($motiveUser, $date) {
                return $params->startDate->toDateTimeString() === $date->clone()->startOfDay()->toDateTimeString()
                    && $params->endDate->toDateTimeString() === $date->clone()->endOfDay()->toDateTimeString()
                    && $params->driverIds === [$motiveUser->id];
            })
            ->willReturn($driverUtilization)
            ->times(1)
            ->mock();

        $drivingHistoricFuelConsumption = 15.7;
        $idleHistoricFuelConsumption = 14.3;
        $historicDriverUtilization = DriverUtilizationData::getTestData(
            1,
            [
                'driving_fuel' => $drivingHistoricFuelConsumption,
                'idle_fuel' => $idleHistoricFuelConsumption,
            ]
        );

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('driverUtilization', DriverUtilizationResource::class)
            ->cached()
            ->method('search')
            ->withArgs(function (SearchDriverUtilizationParams $params) use ($motiveUser, $date) {
                return $params->startDate->toDateTimeString() === $date->clone()->subWeeks(self::HISTORIC_DATA_DURATION_WEEKS)->startOfDay()->toDateTimeString()
                    && $params->endDate->toDateTimeString() === $date->clone()->subDay()->endOfDay()->toDateTimeString()
                    && $params->driverIds === [$motiveUser->id];
            })
            ->willReturn($historicDriverUtilization)
            ->times(1)
            ->mock();

        $distance1 = 20;
        $distance2 = 40;
        $vehicleMileage = VehicleMileageData::getTestData(
            2,
            [
                'distance' => $distance1,
                'vehicle' => (object) ['id' => $vehicleId1],
            ],
            [
                'distance' => $distance2,
                'vehicle' => (object) ['id' => $vehicleId2],
            ]
        );
        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('vehicleMileage', VehicleMileageResource::class)
            ->cached()
            ->method('search')
            ->withArgs(function (SearchVehicleMileageParams $params) use ($vehicleId1, $vehicleId2, $date) {
                return $params->startDate->toDateTimeString() === $date->clone()->subWeeks(self::HISTORIC_DATA_DURATION_WEEKS)->startOfDay()->toDateTimeString()
                    && $params->endDate->toDateTimeString() === $date->clone()->subDay()->endOfDay()->toDateTimeString()
                    && $params->vehicleIds === [$vehicleId1, $vehicleId2];
            })
            ->willReturn($vehicleMileage)
            ->times(1)
            ->mock();

        $service = new MotiveRouteDrivingDataService($clientMock);
        $results = $service->get($userIds, $date);

        /** @var RouteDrivingStats $result */
        $result = $results->first();

        $this->assertEquals($duration * $drivingPeriodsNumber, $result->getTotalDriveTime()->getTotalSeconds());
        $this->assertEquals($kilometers, $result->getTotalDriveDistance()->getKilometers());
        $this->assertEquals($duration, $result->getAverageDriveTimeBetweenServices()->getTotalSeconds());
        $this->assertEquals(2, $result->getAverageDriveDistanceBetweenServices()->getKilometers());
        $this->assertEquals(171, $result->getTotalWorkingTime()->getTotalSeconds());
        $this->assertEquals($drivingFuelConsumption + $idleFuelConsumption, $result->getFuelConsumption());
        $this->assertEquals($drivingHistoricFuelConsumption + $idleHistoricFuelConsumption, $result->getHistoricFuelConsumption());
        $this->assertEquals($distance1 + $distance2, $result->getHistoricVehicleMileage()->getMiles());
    }

    /**
     * @test
     */
    public function it_returns_empty_collection_if_no_user_found(): void
    {
        /** @var FleetRoute $route */
        $route = FleetRouteFactory::make();
        $date = $route->getStartAt()->clone();
        $workdayId = $route->getServicePro()->getWorkdayId();
        $userIds = [$workdayId];

        $clientMock = $this->getMotiveClientMockBuilder()
            ->resource('users', UsersResource::class)
            ->cached()
            ->method('search')
            ->withArgs(function (SearchUsersParams $params) {
                return $params->role === UserRole::DRIVER;
            })
            ->willReturn(collect())
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('users', UsersResource::class)
            ->cached()
            ->preload(function ($value, $method, FindUserParams $params) use ($workdayId) {
                return is_null($value) && $method === 'find' && $params->driverCompanyId === $workdayId;
            });

        $clientMock
            ->shouldReceive('drivingPeriods')
            ->never();

        $clientMock
            ->shouldReceive('driverUtilization')
            ->never();

        $service = new MotiveRouteDrivingDataService($clientMock);
        $result = $service->get($userIds, $date);

        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function it_returns_null_if_no_driving_periods_found(): void
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
            ->resource('drivingPeriods', DrivingPeriodsResource::class)
            ->cached()
            ->method('search')
            ->willReturn(collect())
            ->times(1)
            ->mock();

        $clientMock = $this->getMotiveClientMockBuilder($clientMock)
            ->resource('driverUtilization', DriverUtilizationResource::class)
            ->cached()
            ->method('search')
            ->willReturn(collect())
            ->times(2)
            ->mock();

        Log::shouldReceive('info')->once();

        $service = new MotiveRouteDrivingDataService($clientMock);
        $results = $service->get($userIds, $date);

        $this->assertCount(0, $results);
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
