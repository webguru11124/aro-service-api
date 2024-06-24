<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\OptimizationRules\SetServiceDurationToAverage;
use App\Domain\RouteOptimization\Services\AverageDurationService;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class SetServiceDurationToAverageTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const CUSTOMER_ID = 32454;
    private const INITIAL_APPOINTMENT_DURATION = 35;
    private const REGULAR_APPOINTMENT_DURATION = 18;
    private const AVERAGE_DURATION = 33;

    private SetServiceDurationToAverage $rule;
    private MockInterface|AverageDurationService $mockAverageDurationService;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('aptive.initial_appointment_duration', self::INITIAL_APPOINTMENT_DURATION);
        Config::set('aptive.regular_appointment_duration', self::REGULAR_APPOINTMENT_DURATION);

        $this->mockAverageDurationService = Mockery::mock(AverageDurationService::class);

        $this->rule = new SetServiceDurationToAverage(
            $this->mockAverageDurationService,
        );
    }

    /**
     * @test
     */
    public function it_sets_default_duration_for_initial_appointment(): void
    {
        $this->setMockAverageDurationServiceExpectations();
        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make([
                'description' => 'Test Initial',
                'customerId' => self::CUSTOMER_ID,
            ])],
        ]);

        $optimizationState = $this->buildOptimizationState($route);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $this->assertEquals(self::INITIAL_APPOINTMENT_DURATION, $appointment->getDuration()->getTotalMinutes());
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_sets_average_duration_for_regular_appointment(): void
    {
        $this->setMockAverageDurationServiceExpectations();
        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make([
                'officeId' => TestValue::OFFICE_ID,
                'customerId' => self::CUSTOMER_ID,
                'expectedArrival' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('12:00:00'),
                    Carbon::now()->setTimeFromTimeString('18:00:00'),
                ),
            ])],
        ]);

        $optimizationState = $this->buildOptimizationState($route);

        $this->mockAverageDurationService
            ->shouldReceive('getAverageServiceDuration')
            ->once()
            ->with(TestValue::OFFICE_ID, self::CUSTOMER_ID, Carbon::now()->quarter)
            ->andReturn(Duration::fromMinutes(self::AVERAGE_DURATION));

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $this->assertEquals(self::AVERAGE_DURATION, $appointment->getDuration()->getTotalMinutes());
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_sets_default_duration_when_no_average_duration_calculated(): void
    {
        $this->setMockAverageDurationServiceExpectations();
        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make([
                'officeId' => TestValue::OFFICE_ID,
                'customerId' => self::CUSTOMER_ID,
                'expectedArrival' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('12:00:00'),
                    Carbon::now()->setTimeFromTimeString('18:00:00'),
                ),
            ])],
        ]);

        $optimizationState = $this->buildOptimizationState($route);

        $this->mockAverageDurationService
            ->shouldReceive('getAverageServiceDuration')
            ->once()
            ->with(TestValue::OFFICE_ID, self::CUSTOMER_ID, Carbon::now()->quarter)
            ->andReturn(null);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $this->assertEquals(self::REGULAR_APPOINTMENT_DURATION, $appointment->getDuration()->getTotalMinutes());
        $this->assertSuccessRuleResult($result);
    }

    private function buildOptimizationState(Route $route): OptimizationState
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'timeFrame' => new TimeWindow(
                Carbon::now()->startOfDay(),
                Carbon::now()->endOfDay(),
            ),
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        return $optimizationState;
    }

    private function setMockAverageDurationServiceExpectations(): void
    {
        $this->mockAverageDurationService
            ->shouldReceive('preload')
            ->once()
            ->with(TestValue::OFFICE_ID, Carbon::now()->quarter, self::CUSTOMER_ID);
    }

    protected function getClassRuleName(): string
    {
        return SetServiceDurationToAverage::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
        unset($this->mockAverageDurationService);
    }
}
