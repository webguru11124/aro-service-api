<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitBreakTimeFrames;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitFirstAppointmentExpectedArrival;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReduceWorkTimeRange;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReoptimizationActionFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReverseRoute;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizeRoutes;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\AverageInactivity;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\InactivityBeforeFirstAppointment;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\LongInactivity;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\RouteValidator;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\RouteValidatorsRegister;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\TwoBreaksInARow;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\LunchFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\WorkBreakFactory;

class ReoptimizeRouteTest extends TestCase
{
    private MockInterface|ReoptimizationActionFactory $reoptimizationActionFactoryMock;
    private MockInterface|RouteValidatorsRegister $routeValidatorsRegisterMock;
    private ReoptimizeRoutes $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reoptimizationActionFactoryMock = \Mockery::mock(ReoptimizationActionFactory::class)->makePartial();
        $this->routeValidatorsRegisterMock = \Mockery::mock(RouteValidatorsRegister::class);

        $this->handler = new ReoptimizeRoutes(
            $this->reoptimizationActionFactoryMock,
            $this->routeValidatorsRegisterMock
        );
    }

    /**
     * @test
     */
    public function it_processes_all_routes(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $routesCount = $optimizationState->getRoutes()->count();

        $validatorMock = \Mockery::mock(RouteValidator::class);
        $validatorMock->shouldReceive('validate')
            ->times($routesCount)
            ->andReturnTrue();

        $this->routeValidatorsRegisterMock
            ->shouldReceive('getValidators')
            ->andReturn(new Collection([$validatorMock]));

        $this->handler->process($optimizationState);
    }

    /**
     * @test
     */
    public function it_reverts_route_if_there_is_a_long_waiting(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make();

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $validatorMock = \Mockery::mock(LongInactivity::class)->makePartial();
        $validatorMock->shouldReceive('validate')
            ->andReturnFalse();

        $this->routeValidatorsRegisterMock
            ->shouldReceive('getValidators')
            ->andReturn(new Collection([$validatorMock]));

        $actionMock = \Mockery::mock(ReverseRoute::class);
        $actionMock->shouldReceive('process')
            ->with($route, $optimizationState->getEngine())
            ->once()
            ->andReturn($route);

        $this->reoptimizationActionFactoryMock->shouldReceive('getAction')
            ->with(ReverseRoute::class)
            ->andReturn($actionMock);

        $this->handler->process($optimizationState);
    }

    /**
     * @test
     */
    public function it_reduces_route_time_window_if_there_is_an_average_waiting(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make();
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $validatorMock = \Mockery::mock(AverageInactivity::class)->makePartial();
        $validatorMock->shouldReceive('validate')
            ->andReturnFalse();

        $this->routeValidatorsRegisterMock
            ->shouldReceive('getValidators')
            ->andReturn(new Collection([$validatorMock]));

        $actionMock = \Mockery::mock(ReduceWorkTimeRange::class);
        $actionMock->shouldReceive('process')
            ->with($route, $optimizationState->getEngine())
            ->twice()
            ->andReturn($route);

        $this->reoptimizationActionFactoryMock->shouldReceive('getAction')
            ->with(ReduceWorkTimeRange::class)
            ->andReturn($actionMock);

        $this->handler->process($optimizationState);
    }

    /**
     * @test
     */
    public function it_pulls_first_appointment_to_day_start_if_there_is_waiting_at_day_start(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make();

        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $validatorMock = \Mockery::mock(InactivityBeforeFirstAppointment::class)->makePartial();
        $validatorMock->shouldReceive('validate')
            ->andReturnFalse();

        $this->routeValidatorsRegisterMock
            ->shouldReceive('getValidators')
            ->andReturn(new Collection([$validatorMock]));

        $actionMock = \Mockery::mock(LimitFirstAppointmentExpectedArrival::class);
        $actionMock->shouldReceive('process')
            ->with($route, $optimizationState->getEngine())
            ->once()
            ->andReturn($route);

        $this->reoptimizationActionFactoryMock->shouldReceive('getAction')
            ->with(LimitFirstAppointmentExpectedArrival::class)
            ->andReturn($actionMock);

        $this->handler->process($optimizationState);
    }

    /**
     * @test
     */
    public function it_reduces_breaks_time_frames_if_there_are_two_breaks_in_a_row(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make();

        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $validatorMock = \Mockery::mock(TwoBreaksInARow::class)->makePartial();
        $validatorMock->shouldReceive('validate')
            ->andReturnFalse();

        $this->routeValidatorsRegisterMock
            ->shouldReceive('getValidators')
            ->andReturn(new Collection([$validatorMock]));

        $actionMock = \Mockery::mock(LimitBreakTimeFrames::class);
        $actionMock->shouldReceive('process')
            ->with($route, $optimizationState->getEngine())
            ->times(4)
            ->andReturn($route);

        $this->reoptimizationActionFactoryMock->shouldReceive('getAction')
            ->with(LimitBreakTimeFrames::class)
            ->andReturn($actionMock);

        $this->handler->process($optimizationState);
    }

    /**
     * @dataProvider maxLoadDataProvider
     *
     * @test
     */
    public function it_enforces_max_load_setting(array $routeData, array $expectedValues): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => [],
        ]);

        foreach ($routeData as $datum) {
            if ($datum[0] === WorkEventType::APPOINTMENT) {
                $workEvent = AppointmentFactory::make(['timeWindow' => new TimeWindow(
                    $start = Carbon::parse($datum[1]),
                    $start->clone()->addMinutes(29)
                )]);
            }
            if ($datum[0] === WorkEventType::BREAK) {
                $workEvent = WorkBreakFactory::make(['timeWindow' => new TimeWindow(
                    $start = Carbon::parse($datum[1]),
                    $start->clone()->addMinutes(15)
                )]);
            }
            if ($datum[0] === WorkEventType::LUNCH) {
                $workEvent = LunchFactory::make(['timeWindow' => new TimeWindow(
                    $start = Carbon::parse($datum[1]),
                    $start->clone()->addMinutes(30)
                )]);
            }

            $route->addWorkEvent($workEvent);
        }

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $validatorMock = \Mockery::mock(TwoBreaksInARow::class)->makePartial();
        $validatorMock->shouldReceive('validate')
            ->andReturnFalse();

        $this->routeValidatorsRegisterMock
            ->shouldReceive('getValidators')
            ->andReturn(new Collection([$validatorMock]));

        /** @var Route $reoptimizedRoute */
        $reoptimizedRoute = RouteFactory::make();

        $actionMock = \Mockery::mock(LimitBreakTimeFrames::class);
        $actionMock->shouldReceive('process')
            ->andReturn($reoptimizedRoute);

        $this->reoptimizationActionFactoryMock->shouldReceive('getAction')
            ->andReturn($actionMock);

        $this->handler->process($optimizationState);

        foreach ($route->getWorkBreaks() as $idx => $break) {
            $this->assertEquals($expectedValues[$idx], $break->getMinAppointmentsBefore());
        }
    }

    public static function maxLoadDataProvider(): iterable
    {
        yield [
            [
                [WorkEventType::APPOINTMENT, '08:00'],
                [WorkEventType::APPOINTMENT, '08:30'],
                [WorkEventType::APPOINTMENT, '09:00'],
                [WorkEventType::BREAK, '09:30'],
                [WorkEventType::APPOINTMENT, '10:00'],
                [WorkEventType::APPOINTMENT, '10:30'],
                [WorkEventType::APPOINTMENT, '11:00'],
                [WorkEventType::LUNCH, '12:00'],
                [WorkEventType::BREAK, '12:55'],
                [WorkEventType::APPOINTMENT, '14:00'],
            ],
            [3, 6, 7],
        ];

        yield [
            [
                [WorkEventType::APPOINTMENT, '08:00'],
                [WorkEventType::APPOINTMENT, '08:30'],
                [WorkEventType::BREAK, '09:30'],
                [WorkEventType::APPOINTMENT, '10:00'],
                [WorkEventType::LUNCH, '12:00'],
                [WorkEventType::BREAK, '12:55'],
                [WorkEventType::APPOINTMENT, '14:00'],
            ],
            [2, 3, 4],
        ];

        yield [
            [
                [WorkEventType::APPOINTMENT, '08:00'],
                [WorkEventType::APPOINTMENT, '08:30'],
                [WorkEventType::BREAK, '09:30'],
                [WorkEventType::LUNCH, '12:00'],
                [WorkEventType::APPOINTMENT, '10:00'],
                [WorkEventType::BREAK, '12:55'],
                [WorkEventType::APPOINTMENT, '14:00'],
            ],
            [2, 3, 4],
        ];

        yield [
            [
                [WorkEventType::BREAK, '09:30'],
                [WorkEventType::APPOINTMENT, '08:30'],
                [WorkEventType::LUNCH, '12:00'],
                [WorkEventType::APPOINTMENT, '10:00'],
                [WorkEventType::BREAK, '12:55'],
                [WorkEventType::APPOINTMENT, '14:00'],
            ],
            [1, 2, 3],
        ];

        yield [
            [
                [WorkEventType::BREAK, '09:30'],
                [WorkEventType::LUNCH, '12:00'],
                [WorkEventType::BREAK, '12:55'],
                [WorkEventType::APPOINTMENT, '14:00'],
            ],
            [1, 2, 3],
        ];
    }
}
