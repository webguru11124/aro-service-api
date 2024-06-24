<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\OptimizationRules\SetPreferredServicePro;
use Mockery;
use Tests\TestCase;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class SetPreferredServiceProTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private SetPreferredServicePro $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new SetPreferredServicePro();
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $mockServicePro = Mockery::mock(ServicePro::class);
        $mockServicePro->shouldReceive('getId')->andReturn(1);

        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('getServicePro')->andReturn($mockServicePro);

        $mockAppointment = Mockery::mock(Appointment::class);
        $mockAppointment->shouldReceive('getPreferredTechId')->andReturn(1);
        $mockAppointment->shouldReceive('addSkillFromPreferredTech');

        $mockOptimizationState = Mockery::mock(OptimizationState::class);
        $mockOptimizationState->shouldReceive('getAllAppointments')
            ->andReturn(collect([$mockAppointment]));
        $mockOptimizationState->shouldReceive('getRoutes')
            ->andReturn(collect([$mockRoute]));

        $result = $this->rule->process($mockOptimizationState);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_without_appointments(): void
    {
        $mockOptimizationState = Mockery::mock(OptimizationState::class);
        $mockOptimizationState->shouldReceive('getAllAppointments')->andReturn(collect([]));

        $result = $this->rule->process($mockOptimizationState);

        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_if_no_preferred_tech_is_defined(): void
    {
        $mockAppointment = Mockery::mock(Appointment::class);
        $mockAppointment->shouldReceive('getPreferredTechId')->andReturn(null);

        $mockOptimizationState = Mockery::mock(OptimizationState::class);
        $mockOptimizationState->shouldReceive('getAllAppointments')
            ->andReturn(collect([$mockAppointment]));

        $result = $this->rule->process($mockOptimizationState);

        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_if_preferred_tech_is_not_available_during_date(): void
    {
        $mockServicePro = Mockery::mock(ServicePro::class);
        $mockServicePro->shouldReceive('getId')->andReturn(2);

        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('getServicePro')->andReturn($mockServicePro);

        $mockAppointment = Mockery::mock(Appointment::class);
        $mockAppointment->shouldReceive('getPreferredTechId')->andReturn(1);

        $mockOptimizationState = Mockery::mock(OptimizationState::class);
        $mockOptimizationState->shouldReceive('getAllAppointments')
            ->andReturn(collect([$mockAppointment]));
        $mockOptimizationState->shouldReceive('getRoutes')
            ->andReturn(collect([$mockRoute]));

        $result = $this->rule->process($mockOptimizationState);

        $this->assertTriggeredRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return SetPreferredServicePro::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
