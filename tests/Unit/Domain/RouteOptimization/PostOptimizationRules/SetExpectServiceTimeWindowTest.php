<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\PostOptimizationRules\SetExpectServiceTimeWindow;
use Tests\TestCase;

class SetExpectServiceTimeWindowTest extends TestCase
{
    private SetExpectServiceTimeWindow $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new SetExpectServiceTimeWindow();
    }

    /**
     * @test
     *
     * ::getTimeWindowMinutes
     */
    public function it_returns_time_window_duration(): void
    {
        $result = $this->rule->getTimeWindowMinutes();

        $this->assertGreaterThan(0, $result->getTotalMinutes());
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_id(): void
    {
        $result = $this->rule->id();

        $this->assertEquals('SetExpectServiceTimeWindow', $result);
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_name(): void
    {
        $result = $this->rule->name();

        $this->assertNotEmpty($result);
    }

    /**
     * @test
     *
     * ::getDescription
     */
    public function it_returns_rule_description(): void
    {
        $result = $this->rule->description();

        $this->assertNotEmpty($result);
    }
}
