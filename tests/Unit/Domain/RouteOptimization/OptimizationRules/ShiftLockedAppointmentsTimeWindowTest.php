<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\OptimizationRules\ShiftLockedAppointmentsTimeWindow;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class ShiftLockedAppointmentsTimeWindowTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private ShiftLockedAppointmentsTimeWindow $rule;
    private OptimizationState $optimizationState;
    private TimeWindow $expectedArrival;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new ShiftLockedAppointmentsTimeWindow();
        $this->expectedArrival = new TimeWindow(
            Carbon::tomorrow()->startOfDay(),
            Carbon::tomorrow()->endOfDay()
        );

        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(3, ['expectedArrival' => $this->expectedArrival]),
        ]);

        /** @var OptimizationState $optimizationState */
        $this->optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        /** @var Appointment $firstAppointment */
        $firstAppointment = $this->optimizationState->getAllAppointments()->first();

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();
        $lockTimeWindow = new TimeWindow(
            Carbon::tomorrow()->hour(8),
            Carbon::tomorrow()->hour(8)->minute(30)
        );
        $firstAppointment->lock($lockTimeWindow, $servicePro);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        /** @var Route $route */
        $route = $this->optimizationState->getRoutes()->first();
        $optimizedRoute = RouteFactory::make([
            'workEvents' => $route->getAppointments()->slice(1, 2)->all(),
        ]);

        /** @var OptimizationState $optimizedOptimizationState */
        $optimizedOptimizationState = OptimizationStateFactory::make([
            'routes' => [$optimizedRoute],
            'unassignedAppointments' => $route->getAppointments()->slice(0, 1)->all(),
        ]);

        $this->rule->process($this->optimizationState, $optimizedOptimizationState);

        /** @var Appointment $firstAppointment */
        $firstAppointment = $this->optimizationState->getAllAppointments()->first();

        $this->assertEquals(new TimeWindow(
            Carbon::tomorrow()->hour(8),
            Carbon::tomorrow()->hour(9)
        ), $firstAppointment->getExpectedArrival());

        $this->assertSuccessRuleResult($this->optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_skips_processing_when_there_no_appointments(): void
    {
        $sourceOptimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [],
                ]),
            ],
            'unassignedAppointments' => [],
        ]);
        /** @var Route $route */
        $route = $this->optimizationState->getRoutes()->first();
        $optimizedRoute = RouteFactory::make([
            'workEvents' => [],
        ]);

        /** @var OptimizationState $optimizedOptimizationState */
        $optimizedOptimizationState = OptimizationStateFactory::make([
            'routes' => [$optimizedRoute],
            'unassignedAppointments' => $route->getAppointments()->slice(0, 1)->all(),
        ]);

        $this->rule->process($sourceOptimizationState, $optimizedOptimizationState);

        $this->assertTriggeredRuleResult($sourceOptimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_shift_expected_arrival_if_there_is_no_unassigned_appointments(): void
    {
        /** @var OptimizationState $optimizedOptimizationState */
        $optimizedOptimizationState = clone $this->optimizationState;

        $this->rule->process($this->optimizationState, $optimizedOptimizationState);

        /** @var Appointment $firstAppointment */
        $firstAppointment = $this->optimizationState->getAllAppointments()->first();

        $this->assertEquals(new TimeWindow(
            Carbon::tomorrow()->hour(8),
            Carbon::tomorrow()->hour(8)->minute(30)
        ), $firstAppointment->getExpectedArrival());
        $this->assertTriggeredRuleResult($this->optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_shift_expected_arrival_if_route_is_empty(): void
    {
        /** @var Route $route */
        $route = $this->optimizationState->getRoutes()->first();
        $route->clearWorkEvents();

        $optimizedRoute = RouteFactory::make([
            'workEvents' => $route->getAppointments()->slice(1, 2)->all(),
        ]);

        /** @var OptimizationState $optimizedOptimizationState */
        $optimizedOptimizationState = OptimizationStateFactory::make([
            'routes' => [$optimizedRoute],
            'unassignedAppointments' => $route->getAppointments()->slice(0, 1)->all(),
        ]);

        $this->rule->process($this->optimizationState, $optimizedOptimizationState);

        $this->assertTriggeredRuleResult($this->optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_shift_expected_arrival_if_locked_appointment_not_in_unassigned(): void
    {
        /** @var OptimizationState $optimizedOptimizationState */
        $optimizedOptimizationState = clone $this->optimizationState;

        /** @var Appointment $lockedAppointment */
        $lockedAppointment = AppointmentFactory::make();

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();
        $lockedAppointment->lock(new TimeWindow(
            $lockedAppointment->getTimeWindow()->getStartAt(),
            $lockedAppointment->getTimeWindow()->getEndAt()
        ), $servicePro);

        $optimizedOptimizationState->setUnassignedAppointments(new Collection([$lockedAppointment]));

        $this->rule->process($this->optimizationState, $optimizedOptimizationState);

        /** @var Appointment $firstAppointment */
        $firstAppointment = $this->optimizationState->getAllAppointments()->first();

        $this->assertEquals(new TimeWindow(
            Carbon::tomorrow()->hour(8),
            Carbon::tomorrow()->hour(8)->minute(30)
        ), $firstAppointment->getExpectedArrival());
        $this->assertTriggeredRuleResult($this->optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_it_is_disabled(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'optimizationParams' => new OptimizationParams(disabledRules: ['ShiftLockedAppointmentsTimeWindow']),
        ]);

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        $this->assertSkippedRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    protected function getClassRuleName(): string
    {
        return ShiftLockedAppointmentsTimeWindow::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
        unset($this->optimizationState);
        unset($this->expectedArrival);
    }
}
