<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\BusinessRules;

use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use Tests\TestCase;

class IncreaseTravelSpeedTest extends TestCase
{
    private IncreaseTravelSpeed $increaseTravelSpeed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->increaseTravelSpeed = new IncreaseTravelSpeed();
    }

    /**
     * @test
     *
     * ::getSpeedFactorIncreaseValue
     */
    public function it_returns_speed_factor_increase_value(): void
    {
        $result = $this->increaseTravelSpeed->getSpeedFactorIncreaseValue();

        $this->assertGreaterThan(0.0, $result);
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_name(): void
    {
        $result = $this->increaseTravelSpeed->name();

        $this->assertNotEmpty($result);
    }

    /**
     * @test
     *
     * ::getDescription
     */
    public function it_returns_rule_description(): void
    {
        $result = $this->increaseTravelSpeed->description();

        $this->assertNotEmpty($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->increaseTravelSpeed);
    }
}
