<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitBreakTimeFrames;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\LunchFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\WorkBreakFactory;

class LimitBreakTimeFramesTest extends TestCase
{
    private const DEFAUL_ENGINE = OptimizationEngine::VROOM;

    private MockInterface|RouteOptimizationService $routeOptimizationServiceMock;
    private MockInterface|RouteOptimizationServiceFactory $routeOptimizationServiceFactoryMock;
    private LimitBreakTimeFrames $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeOptimizationServiceMock = Mockery::mock(RouteOptimizationService::class)
            ->makePartial();
        $this->routeOptimizationServiceFactoryMock = Mockery::mock(RouteOptimizationServiceFactory::class);
        $this->routeOptimizationServiceFactoryMock
            ->shouldReceive('getRouteOptimizationService')
            ->andReturn($this->routeOptimizationServiceMock);

        $this->action = new LimitBreakTimeFrames(
            $this->routeOptimizationServiceFactoryMock
        );
    }

    /**
     * @test
     */
    public function it_limits_break_time_frames(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make();
        $route->removeWorkBreaks();

        $route->addWorkEvent(WorkBreakFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(9)->minute(30),
                Carbon::tomorrow()->hour(9)->minute(45)
            ),
            'expectedArrival' => new TimeWindow(
                Carbon::tomorrow()->hour(9)->minute(30),
                Carbon::tomorrow()->hour(10)->minute(30)
            ),
        ]));

        $route->addWorkEvent(LunchFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(11),
                Carbon::tomorrow()->hour(11)->minute(30)
            ),
            'expectedArrival' => new TimeWindow(
                Carbon::tomorrow()->hour(11),
                Carbon::tomorrow()->hour(12)
            ),
        ]));

        $route->addWorkEvent(WorkBreakFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(13),
                Carbon::tomorrow()->hour(13)->minute(15)
            ),
            'expectedArrival' => new TimeWindow(
                Carbon::tomorrow()->hour(13),
                Carbon::tomorrow()->hour(14)
            ),
        ]));

        $this->routeOptimizationServiceMock
            ->shouldReceive('optimizeSingleRoute')
            ->withArgs(function (Route $route) {
                /** @var WorkBreak $firstBreak */
                $firstBreak = $route->getWorkBreaks()->first();
                /** @var WorkBreak $lastBreak */
                $lastBreak = $route->getWorkBreaks()->last();
                /** @var Lunch $lunch */
                $lunch = $route->getLunch()->first();

                return
                    $firstBreak->getExpectedArrival()->getStartAt()->toTimeString() === '09:30:00'
                    && $firstBreak->getExpectedArrival()->getEndAt()->toTimeString() === '10:00:00'
                    && $lunch->getExpectedArrival()->getStartAt()->toTimeString() === '11:15:00'
                    && $lunch->getExpectedArrival()->getEndAt()->toTimeString() === '11:45:00'
                    && $lastBreak->getExpectedArrival()->getStartAt()->toTimeString() === '13:30:00'
                    && $lastBreak->getExpectedArrival()->getEndAt()->toTimeString() === '14:00:00'
                ;
            })
            ->once()
            ->andReturn($route);

        $this->action->process($route, self::DEFAUL_ENGINE);
    }

    /**
     * @test
     */
    public function it_processes_only_2_times(): void
    {
        $route = RouteFactory::make();
        $this->routeOptimizationServiceMock
            ->shouldReceive('optimizeSingleRoute')
            ->times(2)
            ->andReturn($route);

        for ($i = 0; $i <= random_int(3, 10); $i++) {
            $this->action->process($route, self::DEFAUL_ENGINE);
        }
    }
}
