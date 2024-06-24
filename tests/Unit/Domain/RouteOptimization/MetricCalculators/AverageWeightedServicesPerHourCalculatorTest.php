<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\MetricCalculators;

use App\Domain\RouteOptimization\MetricCalculators\AverageWeightedServicesPerHourCalculator;
use Tests\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;

class AverageWeightedServicesPerHourCalculatorTest extends TestCase
{
    private const WEIGHTED_SERVICES = 15;
    private AverageWeightedServicesPerHourCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new AverageWeightedServicesPerHourCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_metric_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWeightedServices' => self::WEIGHTED_SERVICES,
            'totalWorkingTime' => 400,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(1.15, $metric->getWeightedScore());
        $this->assertEquals(2.3, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_sets_maximum_possible_score(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWeightedServices' => self::WEIGHTED_SERVICES,
            'totalWorkingTime' => 300,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(1.25, $metric->getWeightedScore());
        $this->assertEquals(3, $metric->getValue());
    }

    /**
     * @test
     */
    public function it_returns_zero_when_total_working_time_is_zero(): void
    {
        $routeStats = RouteStatsFactory::make([
            'totalWeightedServices' => self::WEIGHTED_SERVICES,
            'totalWorkingTime' => 0,
        ]);

        $metric = $this->calculator->calculate($routeStats);

        $this->assertEquals(0, $metric->getWeightedScore());
        $this->assertEquals(0, $metric->getValue());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->calculator);
    }
}
