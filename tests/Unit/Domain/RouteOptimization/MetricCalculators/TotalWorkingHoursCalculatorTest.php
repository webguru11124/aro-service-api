<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\TotalWorkingHoursCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class TotalWorkingHoursCalculatorTest extends TestCase
{
    private TotalWorkingHoursCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TotalWorkingHoursCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 300, // 5 hour
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.63, round($metric->getWeightedScore(), 2));
        $this->assertEquals(5, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 480, // 8 hours
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(1, $metric->getWeightedScore());
        $this->assertEquals(8, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_minimum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 1020,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0, $metric->getWeightedScore());
        $this->assertEquals(17, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
