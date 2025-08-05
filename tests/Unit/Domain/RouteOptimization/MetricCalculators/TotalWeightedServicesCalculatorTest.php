<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\TotalWeightedServicesCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class TotalWeightedServicesCalculatorTest extends TestCase
{
    private TotalWeightedServicesCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TotalWeightedServicesCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWeightedServices' => 6,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.535, $metric->getWeightedScore());
        $this->assertEquals(6, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWeightedServices' => 15,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(1.25, $metric->getWeightedScore());
        $this->assertEquals(15, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
