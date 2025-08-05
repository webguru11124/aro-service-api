<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects\RouteMetrics;

use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Weight;
use Tests\TestCase;

class WeightTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_weight(): void
    {
        $testValue = $this->faker->randomFloat(2, 0, 1);
        $weight = new Weight($testValue);

        $this->assertEquals($testValue, $weight->value());
    }

    /**
     * @test
     */
    public function it_throw_exception_when_given_weight_value_is_below_min(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Weight(-1);
    }

    /**
     * @test
     */
    public function it_throw_exception_when_given_weight_value_is_above_max(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Weight(2);
    }
}
