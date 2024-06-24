<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\AverageMilesBetweenServicesCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class AverageMilesBetweenServicesCalculatorTest extends TestCase
{
    private AverageMilesBetweenServicesCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new AverageMilesBetweenServicesCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'averageDriveDistanceBetweenServices' => 8000,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.28, round($metric->getWeightedScore(), 2));
        $this->assertEquals(4.97, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'averageDriveDistanceBetweenServices' => 5000,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.375, $metric->getWeightedScore());
        $this->assertEquals(3.11, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_minimum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'averageDriveDistanceBetweenServices' => 30000,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0, $metric->getWeightedScore());
        $this->assertEquals(18.64, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
