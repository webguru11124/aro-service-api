<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\MetricCalculators\TotalWeightedServicesCalculator;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;

class TotalWeightedServiceMetricFactory extends AbstractFactory
{
    protected function single($overrides = []): Metric
    {
        $value = $overrides['value'] ?? random_int(5, TotalWeightedServicesCalculator::GOLD_STANDARD_NUMBER_OF_WEIGHTED_SERVICES);

        return new Metric(
            MetricKey::TOTAL_WEIGHTED_SERVICES,
            $value,
            new Weight(TotalWeightedServicesCalculator::METRIC_WEIGHT),
            new Score($value / TotalWeightedServicesCalculator::WEIGHTED_SERVICES_PER_ONE_POINT)
        );
    }
}
