<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Tools\Factories\OptimizationStateFactory;
use App\Domain\RouteOptimization\PostOptimizationRules\DetectRescheduledConfirmedAppointments;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\DetectRescheduledConfirmedAppointmentsCaster;

class DetectRescheduledConfirmedAppointmentsCasterTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;

    private DetectRescheduledConfirmedAppointments $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DetectRescheduledConfirmedAppointments();
    }

    /** @test */
    public function it_can_detect_confirmed_appointments(): void
    {
        $state = OptimizationStateFactory::make([
            'unassignedAppointments' => [
                AppointmentFactory::make(['notified' => true]),
            ],
        ]);

        Log::shouldReceive('warning')->once();

        $caster = new DetectRescheduledConfirmedAppointmentsCaster();
        $result = $caster->process(Carbon::today(), $state, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    /** @test */
    public function it_can_detect_no_confirmed_appointments(): void
    {
        $state = OptimizationStateFactory::make([
            'unassignedAppointments' => [
                AppointmentFactory::make(['notified' => false]),
            ],
        ]);

        Log::shouldReceive('warning')->never();

        $caster = new DetectRescheduledConfirmedAppointmentsCaster();
        $result = $caster->process(Carbon::today(), $state, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return get_class($this->rule);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->rule);
    }
}
