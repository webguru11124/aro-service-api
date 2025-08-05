<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Services;

use App\Domain\Contracts\Services\RouteCompletionStatsService;
use App\Domain\Contracts\Services\RouteDrivingStatsService;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\Services\FleetRouteStatsService;
use App\Infrastructure\Services\Motive\MotiveVehicleTrackingDataService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\FleetRouteFactory;
use Tests\Tools\Factories\Tracking\FleetRouteStateFactory;
use Tests\Tools\Factories\Tracking\RouteDrivingStatsFactory;
use Tests\Tools\Factories\Tracking\RouteTrackingDataFactory;
use Tests\Tools\TestValue;

class FleetRouteStatsServiceTest extends TestCase
{
    private RouteCompletionStatsService|MockInterface $mockRouteAdherenceService;
    private RouteDrivingStatsService|MockInterface $mockRouteActualStatisticsService;
    private MotiveVehicleTrackingDataService|MockInterface $mockVehicleTrackingService;

    private FleetRouteStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRouteAdherenceService = Mockery::mock(RouteCompletionStatsService::class);
        $this->mockRouteActualStatisticsService = Mockery::mock(RouteDrivingStatsService::class);
        $this->mockVehicleTrackingService = Mockery::mock(MotiveVehicleTrackingDataService::class);

        $this->service = new FleetRouteStatsService(
            $this->mockRouteAdherenceService,
            $this->mockRouteActualStatisticsService,
            $this->mockVehicleTrackingService,
        );
    }

    /**
     * @test
     */
    public function it_updates_driving_stats(): void
    {
        $fleetRoutes = [
            FleetRouteFactory::make([
                'id' => TestValue::ROUTE_ID,
                'drivingStats' => RouteDrivingStatsFactory::make([
                    'fuelConsumption' => 1,
                ]),
            ]),
        ];

        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'fleetRoutes' => $fleetRoutes,
        ]);

        $this->mockRouteAdherenceService
            ->shouldReceive('updateCompletionStats')
            ->once()
            ->with($fleetRouteState);

        /** @var ServicePro $servicePro */
        $servicePro = $fleetRoutes[0]->getServicePro();

        $statistics = RouteDrivingStatsFactory::make([
            'fuelConsumption' => 7,
        ]);
        $this->mockRouteActualStatisticsService
            ->shouldReceive('get')
            ->withArgs(
                fn (array $userIds, CarbonInterface $date)
                    => $userIds === [$servicePro->getWorkdayId()]
                    && $date->toDateString() === $fleetRouteState->getDate()->toDateString()
            )
            ->times(count($fleetRoutes))
            ->andReturn(collect([$servicePro->getWorkdayId() => $statistics]));

        $this->mockVehicleTrackingService
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                fn (array $userIds, CarbonInterface $date)
                    => $userIds === [$servicePro->getWorkdayId()]
                    && $date->toDateString() === $fleetRouteState->getDate()->toDateString()
            )
            ->andReturn(collect([$servicePro->getWorkdayId() => RouteTrackingDataFactory::make()]));

        $result = $this->service->updateActualStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $result->getFleetRoutes()->first();
        $drivingStats = $route->getDrivingStats();
        $this->assertEquals(7, $drivingStats->getFuelConsumption());
    }

    /**
     * @test
     */
    public function it_does_not_updates_driving_data_when_no_actual_stats_returned(): void
    {
        $fleetRoutes = [
            FleetRouteFactory::make([
                'id' => TestValue::ROUTE_ID,
                'drivingStats' => RouteDrivingStatsFactory::make([
                    'fuelConsumption' => 0,
                ]),
            ]),
        ];

        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'fleetRoutes' => $fleetRoutes,
        ]);
        /** @var ServicePro $servicePro */
        $servicePro = $fleetRoutes[0]->getServicePro();

        $this->mockRouteAdherenceService
            ->shouldReceive('updateCompletionStats')
            ->once()
            ->with($fleetRouteState);

        $this->mockRouteActualStatisticsService
            ->shouldReceive('get')
            ->times(count($fleetRoutes))
            ->andReturn(collect());

        $this->mockVehicleTrackingService
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect([$servicePro->getWorkdayId() => RouteTrackingDataFactory::make()]));

        $result = $this->service->updateActualStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $result->getFleetRoutes()->first();
        $drivingStats = $route->getDrivingStats();
        $this->assertEquals(0, $drivingStats->getFuelConsumption());
    }

    /**
     * @test
     */
    public function it_does_not_updates_tracking_data_if_not_today(): void
    {
        $fleetRoutes = [
            FleetRouteFactory::make([
                'id' => TestValue::ROUTE_ID,
            ]),
        ];

        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'fleetRoutes' => $fleetRoutes,
            'date' => Carbon::today()->subDay(),
        ]);

        $this->mockRouteAdherenceService
            ->shouldReceive('updateCompletionStats')
            ->once()
            ->with($fleetRouteState);

        $this->mockRouteActualStatisticsService
            ->shouldReceive('get')
            ->times(count($fleetRoutes))
            ->andReturn(collect());

        $this->mockVehicleTrackingService
            ->shouldReceive('get')
            ->once()
            ->never();

        $result = $this->service->updateActualStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $result->getFleetRoutes()->first();
        $this->assertNull($route->getTrackingData());
    }

    /**
     * @test
     */
    public function it_does_not_updates_tracking_data_when_no_tracking_data_returned(): void
    {
        $fleetRoutes = [
            FleetRouteFactory::make([
                'id' => TestValue::ROUTE_ID,
            ]),
        ];

        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'fleetRoutes' => $fleetRoutes,
        ]);

        $this->mockRouteAdherenceService
            ->shouldReceive('updateCompletionStats')
            ->once()
            ->with($fleetRouteState);

        $this->mockRouteActualStatisticsService
            ->shouldReceive('get')
            ->times(count($fleetRoutes))
            ->andReturn(collect());

        $this->mockVehicleTrackingService
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect());

        $result = $this->service->updateActualStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $result->getFleetRoutes()->first();
        $this->assertNull($route->getTrackingData());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockRouteAdherenceService);
        unset($this->mockRouteActualStatisticsService);
        unset($this->mockVehicleTrackingService);
    }
}
