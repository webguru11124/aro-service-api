<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\TotalDriveTimeCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class TotalDriveTimeCalculatorTest extends TestCase
{
    private TotalDriveTimeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TotalDriveTimeCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalDriveTime' => 150,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.25, $metric->getWeightedScore());
        $this->assertEquals(150, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalDriveTime' => 60, // 1 hour
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.5, $metric->getWeightedScore());
        $this->assertEquals(60, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_minimum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalDriveTime' => 180,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0, $metric->getWeightedScore());
        $this->assertEquals(180, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
