<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\TotalDriveMilesCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class TotalDriveMilesCalculatorTest extends TestCase
{
    private TotalDriveMilesCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TotalDriveMilesCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalDriveDistance' => 100000, // meters
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.24, round($metric->getWeightedScore(), 2));
        $this->assertEquals(62.14, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalDriveDistance' => 60000,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.25, $metric->getWeightedScore());
        $this->assertEquals(37.28, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_minimum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalDriveDistance' => 180000,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0, $metric->getWeightedScore());
        $this->assertEquals(111.85, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
