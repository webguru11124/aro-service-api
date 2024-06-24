<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\Exceptions\InvalidTotalWeightOfMetricsException;
use App\Domain\RouteOptimization\MetricCalculators\TotalWeightedServicesCalculator;
use App\Domain\RouteOptimization\Services\RouteOptimizationScoreCalculationService;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteStatsFactory;
use Tests\Tools\Factories\TotalWeightedServiceMetricFactory;

class RouteOptimizationScoreCalculationServiceTest extends TestCase
{
    private RouteOptimizationScoreCalculationService $service;
    private MockInterface|TotalWeightedServicesCalculator $mockCalculator;
    private MockInterface|RouteStatisticsService $mockRouteStatisticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCalculator = Mockery::mock(TotalWeightedServicesCalculator::class);
        $this->mockRouteStatisticsService = Mockery::mock(RouteStatisticsService::class);

        $this->service = new RouteOptimizationScoreCalculationService(
            $this->mockRouteStatisticsService,
            [$this->mockCalculator]
        );
    }

    /**
     * @test
     */
    public function it_calculate_metrics(): void
    {
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [AppointmentFactory::make()],
                ]),
            ],
        ]);

        /** @var Metric $metric */
        $metric = TotalWeightedServiceMetricFactory::make();

        $this->mockRouteStatisticsService->shouldReceive('getStats')
            ->once()
            ->andReturn(RouteStatsFactory::make());

        $this->mockCalculator->shouldReceive('calculate')
            ->once()
            ->andReturn($metric);

        $result = $this->service->calculate($optimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        $this->assertTrue($route->hasMetric(MetricKey::TOTAL_WEIGHTED_SERVICES));

        $resultMetric = $route->getMetric(MetricKey::TOTAL_WEIGHTED_SERVICES);
        $this->assertEquals($metric->getValue(), $resultMetric->getValue());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_total_weight_of_metric_is_above_max_allowed_value(): void
    {
        $service = new RouteOptimizationScoreCalculationService(
            $this->mockRouteStatisticsService,
            [
                $this->mockCalculator,
                $this->mockCalculator,
            ]
        );
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [AppointmentFactory::make()],
                ]),
            ],
        ]);

        $metric = new Metric(
            MetricKey::TOTAL_WEIGHTED_SERVICES,
            5,
            new Weight(0.6),
            new Score(5 / TotalWeightedServicesCalculator::WEIGHTED_SERVICES_PER_ONE_POINT)
        );

        $this->mockRouteStatisticsService->shouldReceive('getStats')
            ->once()
            ->andReturn(RouteStatsFactory::make());

        $this->mockCalculator->shouldReceive('calculate')
            ->andReturn($metric);

        $this->expectException(InvalidTotalWeightOfMetricsException::class);
        $service->calculate($optimizationState);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
        unset($this->mockCalculator);
        unset($this->mockRouteStatisticsService);
    }
}
