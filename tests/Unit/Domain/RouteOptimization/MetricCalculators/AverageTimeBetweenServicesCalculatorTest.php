<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\AverageTimeBetweenServicesCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class AverageTimeBetweenServicesCalculatorTest extends TestCase
{
    private AverageTimeBetweenServicesCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new AverageTimeBetweenServicesCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 7 * 60,
            'totalServiceTime' => 5 * 60,
            'totalAppointments' => 11,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.19, round($metric->getWeightedScore(), 2));
        $this->assertEquals(12, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 7 * 60,
            'totalServiceTime' => 5 * 60,
            'totalAppointments' => 21,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.375, $metric->getWeightedScore());
        $this->assertEquals(6, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_minimum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 7 * 60,
            'totalServiceTime' => 5 * 60,
            'totalAppointments' => 6,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0, $metric->getWeightedScore());
        $this->assertEquals(24, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_calculates_average_score_when_there_no_more_than_one_appointment(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWorkingTime' => 40,
            'totalServiceTime' => 30,
            'totalAppointments' => 1,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0.28, round($metric->getWeightedScore(), 2));
        $this->assertEquals(10, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
