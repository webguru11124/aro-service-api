<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Exceptions\InvalidTotalWeightOfMetricsException;
use App\Domain\RouteOptimization\MetricCalculators\RouteMetricCalculator;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;

class RouteOptimizationScoreCalculationService
{
    private const MAX_TOTAL_WEIGHT = 1; // Total weight can't be greater than 100%

    /** @var array<Metric>  */
    private array $metrics = [];

    public function __construct(
        private RouteStatisticsService $routeStatisticsService,
        /** @var array<RouteMetricCalculator> $calculators */
        private readonly array $calculators,
    ) {
    }

    /**
     * Calculate Metrics for each route in the OptimizationState and the Route Optimization Score
     *
     * @param OptimizationState $state
     *
     * @return OptimizationState
     * @throws InvalidTotalWeightOfMetricsException
     */
    public function calculate(OptimizationState $state): OptimizationState
    {
        /** @var Route $route */
        foreach ($state->getRoutes() as $route) {
            $this->calculateRouteMetrics($route);
            $this->validateMetrics();
        }

        return $state;
    }

    private function calculateRouteMetrics(Route $route): void
    {
        $this->metrics = [];
        $routeStats = $this->routeStatisticsService->getStats($route);

        foreach ($this->calculators as $calculator) {
            $metric = $calculator->calculate($routeStats);
            $this->metrics[] = $metric;
            $route->setMetric($metric);
        }
    }

    /**
     * Validate that the individual metric scores add up correctly
     *
     * @return void
     * @throws InvalidTotalWeightOfMetricsException
     */
    private function validateMetrics(): void
    {
        $totalWeight = 0;
        foreach ($this->metrics as $metric) {
            $totalWeight += $metric->getWeight()->value();
        }

        if ($totalWeight > self::MAX_TOTAL_WEIGHT) {
            throw InvalidTotalWeightOfMetricsException::instance($totalWeight);
        }
    }
}
