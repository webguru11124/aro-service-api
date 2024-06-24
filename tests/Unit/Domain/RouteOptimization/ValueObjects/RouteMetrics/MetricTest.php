<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects\RouteMetrics;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use Tests\TestCase;

class MetricTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_metric(): void
    {
        $testValue = 12;
        $weightValue = 0.1;
        $scoreValue = 4.5;

        $metric = new Metric(
            MetricKey::TOTAL_WEIGHTED_SERVICES,
            $testValue,
            new Weight($weightValue),
            new Score($scoreValue)
        );

        $this->assertEquals('Total Weighted Services', $metric->getName());
        $this->assertEquals($testValue, $metric->getValue());
        $this->assertEquals(MetricKey::TOTAL_WEIGHTED_SERVICES, $metric->getKey());
        $this->assertEquals($scoreValue, $metric->getScore()->value());
        $this->assertEquals($weightValue, $metric->getWeight()->value());
        $this->assertEquals(0.45, $metric->getWeightedScore());
        $this->assertEquals(0.5, $metric->getMaxPossibleWeightedScore());
    }
}
