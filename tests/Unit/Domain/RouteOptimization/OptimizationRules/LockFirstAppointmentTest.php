<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\OptimizationRules\LockFirstAppointment;
use App\Domain\RouteOptimization\Services\BusinessDaysService;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\StartLocationFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class LockFirstAppointmentTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private MockInterface|BusinessDaysService $businessDaysServiceMock;
    private LockFirstAppointment $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessDaysServiceMock = \Mockery::mock(BusinessDaysService::class);
        $this->rule = new LockFirstAppointment($this->businessDaysServiceMock);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $this->businessDaysServiceMock
            ->shouldReceive('needsFirstAppointmentLock')
            ->andReturn(true);

        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => [
                StartLocationFactory::make(),
                ...AppointmentFactory::many(3),
            ],
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $result = $this->rule->process($optimizationState);

        /** @var Route $processedRoute */
        $processedRoute = $optimizationState->getRoutes()->first();
        /** @var array<Appointment> $appointments */
        $appointments = $processedRoute->getAppointments()->all();

        $serviceProPersonalSkill = $route->getServicePro()->getPersonalSkill();

        $appointment1ExpectedArrival = $appointments[1]->getExpectedArrival();
        $appointment2ExpectedArrival = $appointments[2]->getExpectedArrival();

        $this->assertTrue($appointments[0]->isLocked());
        $this->assertEquals($serviceProPersonalSkill, $appointments[0]->getSkills()->last());

        $this->assertEquals($appointments[0]->getExpectedArrival()->getStartAt(), $appointments[0]->getTimeWindow()->getStartAt());
        $this->assertEquals($appointments[0]->getExpectedArrival()->getEndAt(), $appointments[0]->getTimeWindow()->getEndAt());

        $this->assertEquals($appointments[1]->getExpectedArrival()->getStartAt(), $appointments[0]->getTimeWindow()->getEndAt()->clone()->addMinute());
        $this->assertEquals($appointments[1]->getExpectedArrival()->getEndAt(), $appointment1ExpectedArrival->getEndAt());

        $this->assertEquals($appointments[2]->getExpectedArrival()->getStartAt(), $appointments[0]->getTimeWindow()->getEndAt()->clone()->addMinute());
        $this->assertEquals($appointments[2]->getExpectedArrival()->getEndAt(), $appointment2ExpectedArrival->getEndAt());

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_doesnt_lock_first_appointment_if_it_doesnt_need(): void
    {
        $this->businessDaysServiceMock
            ->shouldReceive('needsFirstAppointmentLock')
            ->andReturn(false);

        $expectedArrival = new TimeWindow(
            Carbon::tomorrow()->startOfDay(),
            Carbon::tomorrow()->endOfDay()
        );

        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(
                $this->faker->numberBetween(3, 7),
                ['expectedArrival' => $expectedArrival]
            ),
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        $result = $this->rule->process($optimizationState);

        foreach ($optimizationState->getAllAppointments() as $appointment) {
            $this->assertEquals($expectedArrival, $appointment->getExpectedArrival());
            $this->assertFalse($appointment->isLocked());
        }

        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     */
    public function it_leaves_timed_appointment_unmodified_when_time_window_doesnt_overlap_locked_spot(): void
    {
        $startAt = Carbon::tomorrow()->startOfDay()->addHours(13);
        $endAt = Carbon::tomorrow()->startOfDay()->addHours(15);

        $this->businessDaysServiceMock
            ->shouldReceive('needsFirstAppointmentLock')
            ->andReturn(true);

        $expectedArrival = new TimeWindow(
            Carbon::tomorrow()->startOfDay(),
            Carbon::tomorrow()->endOfDay()
        );

        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(3, ['expectedArrival' => $expectedArrival]),
        ]);

        $timedAppointment = AppointmentFactory::make([
            'expectedArrival' => new TimeWindow(
                $startAt,
                $endAt,
            ),
        ]);

        $route->addWorkEvent($timedAppointment);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $result = $this->rule->process($optimizationState);

        $this->assertEquals($startAt, $timedAppointment->getExpectedArrival()->getStartAt());
        $this->assertEquals($endAt, $timedAppointment->getExpectedArrival()->getEndAt());
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_handles_correctly_route_without_appointments(): void
    {
        $this->businessDaysServiceMock
            ->shouldReceive('needsFirstAppointmentLock')
            ->andReturn(true);

        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => [],
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        $result = $this->rule->process($optimizationState);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_handles_correctly_route_with_late_end_at(): void
    {
        $this->businessDaysServiceMock
            ->shouldReceive('needsFirstAppointmentLock')
            ->andReturn(true);

        /** @var Route $controlRoute */
        $controlRoute = RouteFactory::make();

        $workEventOverrides = [
            // First appointment has EndAt later than in another appointment expected EndAt
            AppointmentFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->setTimeFromTimeString('10:30:00'),
                    Carbon::tomorrow()->setTimeFromTimeString('10:59:00')
                ),
                'expectedArrival' => new TimeWindow(
                    Carbon::tomorrow()->setTimeFromTimeString('08:00:00'),
                    Carbon::tomorrow()->setTimeFromTimeString('10:00:00')
                ),
            ]),
            AppointmentFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->setTimeFromTimeString('14:30:00'),
                    Carbon::tomorrow()->setTimeFromTimeString('14:59:00')
                ),
                'expectedArrival' => new TimeWindow(
                    Carbon::tomorrow()->setTimeFromTimeString('08:00:00'),
                    Carbon::tomorrow()->setTimeFromTimeString('10:00:00')
                ),
            ]),
        ];

        /** @var Route $errantRoute */
        $errantRoute = RouteFactory::make(['workEvents' => $workEventOverrides]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$controlRoute, $errantRoute],
        ]);

        $result = $this->rule->process($optimizationState);

        $this->assertTrue($controlRoute->getAppointments()->first()->isLocked());
        $this->assertFalse($errantRoute->getAppointments()->first()->isLocked());
        $this->assertSuccessRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return LockFirstAppointment::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
        unset($this->businessDaysServiceMock);
    }
}
