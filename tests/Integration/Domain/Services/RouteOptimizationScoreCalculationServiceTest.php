<?php

declare(strict_types=1);

namespace Tests\Integration\Domain\Services;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\Services\RouteOptimizationScoreCalculationService;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Average;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteStatsFactory;

class RouteOptimizationScoreCalculationServiceTest extends TestCase
{
    private RouteOptimizationScoreCalculationService $service;
    private MockInterface|RouteStatisticsService $mockRouteStatisticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRouteStatisticsService = Mockery::mock(RouteStatisticsService::class);

        app()->bind(RouteStatisticsService::class, function ($app) {
            return $this->mockRouteStatisticsService;
        });

        $this->service = app(RouteOptimizationScoreCalculationService::class);
    }

    /**
     * @test
     */
    public function it_calculates_optimization_score_using_all_available_calculators(): void
    {
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make(),
            ],
        ]);

        $this->mockRouteStatisticsService
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(RouteStatsFactory::make());

        $result = $this->service->calculate($optimizationState);

        $expectedMetrics = [
            MetricKey::OPTIMIZATION_SCORE->value,
            MetricKey::TOTAL_WEIGHTED_SERVICES->value,
            MetricKey::TOTAL_WORKING_HOURS->value,
            MetricKey::AVERAGE_TIME_BETWEEN_SERVICES->value,
            MetricKey::AVERAGE_MILES_BETWEEN_SERVICES->value,
            MetricKey::AVERAGE_WEIGHTED_SERVICES_PER_HOUR->value,
            MetricKey::TOTAL_DRIVE_TIME->value,
            MetricKey::TOTAL_DRIVE_MILES->value,
        ];
        $scores = $result->getAverageScores();

        $resultMetrics = $scores->map(fn (Average $average) => $average->getKey()->value)->all();

        $this->assertEquals($expectedMetrics, $resultMetrics);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockRouteStatisticsService);
        unset($this->service);
    }
}
