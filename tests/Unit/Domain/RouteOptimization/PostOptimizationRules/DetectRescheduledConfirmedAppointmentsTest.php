<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationRules;

use Tests\TestCase;
use App\Domain\RouteOptimization\PostOptimizationRules\DetectRescheduledConfirmedAppointments;

class DetectRescheduledConfirmedAppointmentsTest extends TestCase
{
    private DetectRescheduledConfirmedAppointments $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new DetectRescheduledConfirmedAppointments();
    }

    /**
     * @test
     */
    public function it_returns_id(): void
    {
        $this->assertEquals('DetectRescheduledConfirmedAppointments', $this->rule->id());
    }

    /**
     * @test
     */
    public function it_returns_name(): void
    {
        $this->assertEquals('Detect Rescheduled Confirmed Appointments', $this->rule->name());
    }

    /**
     * @test
     */
    public function it_returns_description(): void
    {
        $this->assertEquals('This rule detects if confirmed appointments are on rescheduled routes.', $this->rule->description());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->rule);
    }
}
