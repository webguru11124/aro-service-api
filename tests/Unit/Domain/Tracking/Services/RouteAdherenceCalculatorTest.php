<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Services;

use App\Domain\Tracking\Services\RouteAdherenceCalculator;
use PHPUnit\Framework\TestCase;

class RouteAdherenceCalculatorTest extends TestCase
{
    private RouteAdherenceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RouteAdherenceCalculator();
    }

    /**
     * @test
     */
    public function it_calculates_exact_route_adherence(): void
    {
        $optimized = [1, 2, 3, 4];
        $completed = [1, 2, 3, 4];

        $adherence = $this->calculator->calculateRouteAdherence($optimized, $completed);

        $this->assertEquals(100.0, $adherence);
    }

    /**
     * @test
     */
    public function it_calculates_partial_route_adherence(): void
    {
        $optimized = [1, 2, 3, 4];
        $completed = [1, 4, 2, 3];

        $adherence = $this->calculator->calculateRouteAdherence($optimized, $completed);

        $this->assertEquals(75.0, $adherence);
    }

    /**
     * @test
     */
    public function it_handles_empty_arrays(): void
    {
        $optimized = [];
        $completed = [];

        $adherence = $this->calculator->calculateRouteAdherence($optimized, $completed);

        $this->assertNull($adherence);
    }

    protected function tearDown(): void
    {
        unset($this->calculator);
        parent::tearDown();
    }
}
