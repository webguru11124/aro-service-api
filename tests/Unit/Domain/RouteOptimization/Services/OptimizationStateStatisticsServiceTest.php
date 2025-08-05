<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Services\OptimizationStateStatisticsService;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteStatsFactory;

class OptimizationStateStatisticsServiceTest extends TestCase
{
    private OptimizationStateStatisticsService $service;
    private MockInterface|RouteStatisticsService $mockRouteStatisticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRouteStatisticsService = Mockery::mock(RouteStatisticsService::class);
        $this->service = new OptimizationStateStatisticsService(
            $this->mockRouteStatisticsService,
        );
    }

    /**
     * @test
     *
     * ::getStats
     */
    public function it_calculates_statistics_correctly(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => RouteFactory::many(2),
        ]);

        $routeStats1 = RouteStatsFactory::make([
            'totalAppointments' => 5,
            'totalServiceTime' => 150,
            'totalWorkingTime' => 420,
        ]);
        $routeStats2 = RouteStatsFactory::make([
            'totalAppointments' => 10,
            'totalServiceTime' => 300,
            'totalWorkingTime' => 360,
        ]);

        $this->mockRouteStatisticsService
            ->shouldReceive('getStats')
            ->andReturn($routeStats1, $routeStats2);

        $result = $this->service->getStats($optimizationState);

        $this->assertEquals(
            $routeStats1->getTotalDriveTime()->getTotalSeconds() + $routeStats2->getTotalDriveTime()->getTotalSeconds(),
            $result->getTotalDriveTime()->getTotalSeconds()
        );
        $this->assertEquals(
            $routeStats1->getTotalDriveDistance()->getMeters() + $routeStats2->getTotalDriveDistance()->getMeters(),
            $result->getTotalDriveDistance()->getMeters()
        );
        $this->assertEquals(2, $result->getServicesPerHour());
        $this->assertEquals(6.5, $result->getAverageDailyWorkingHours());
        $this->assertEquals(15, $result->getTotalAssignedAppointments());
        $this->assertEquals(3, $result->getTotalUnassignedAppointments());
        $this->assertEquals(2, $result->getTotalRoutes());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
        unset($this->mockRouteStatisticsService);
    }
}
