<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReduceWorkTimeRange;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\ReservedTimeFactory;
use Tests\Tools\Factories\RouteFactory;

class ReduceWorkTimeRangeTest extends TestCase
{
    use ProcessesOnce;

    private const WAITING_SECONDS = 1200;
    private const DEFAUL_ENGINE = OptimizationEngine::VROOM;

    private MockInterface|RouteOptimizationService $routeOptimizationServiceMock;
    private MockInterface|RouteOptimizationServiceFactory $routeOptimizationServiceFactoryMock;
    private ReduceWorkTimeRange $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeOptimizationServiceMock = Mockery::mock(RouteOptimizationService::class)
            ->makePartial();
        $this->routeOptimizationServiceFactoryMock = Mockery::mock(RouteOptimizationServiceFactory::class);
        $this->routeOptimizationServiceFactoryMock
            ->shouldReceive('getRouteOptimizationService')
            ->andReturn($this->routeOptimizationServiceMock);

        $this->action = new ReduceWorkTimeRange(
            $this->routeOptimizationServiceFactoryMock
        );
    }

    /**
     * @test
     */
    public function it_skips_reduce_work_time_range_if_route_has_reserved_time(): void
    {
        $reservedTime = ReservedTimeFactory::make();

        $route = $this->getTestRoute();
        $route->addWorkEvent($reservedTime);

        $this->routeOptimizationServiceMock
            ->shouldReceive('getRouteOptimizationService')
            ->never();

        $this->action->process($route, self::DEFAUL_ENGINE);
    }

    /**
     * @test
     */
    public function it_reduces_work_time_range(): void
    {
        $route = $this->getTestRoute();
        $initialWorkStart = $route->getTimeWindow()->getStartAt()->clone();
        $initialWorkEnd = $route->getTimeWindow()->getEndAt()->clone();
        $expectedWorkEnd = $initialWorkEnd->subSeconds(self::WAITING_SECONDS / 2);

        $this->routeOptimizationServiceMock
            ->shouldReceive('optimizeSingleRoute')
            ->withArgs(function (Route $route) use ($initialWorkStart, $expectedWorkEnd) {

                return $route->getTimeWindow()->getStartAt() == $initialWorkStart
                    && $route->getTimeWindow()->getEndAt() == $expectedWorkEnd;
            })
            ->once()
            ->andReturn($route);

        $this->action->process($route, self::DEFAUL_ENGINE);
    }

    private function getTestRoute(): Route
    {
        /** @var Route $route */
        $route = RouteFactory::make();
        foreach ($route->getWorkEvents() as $workEvent) {
            if ($workEvent instanceof Waiting) {
                $route->removeWorkEvent($workEvent);
            }
        }

        $firstAppointment = $route->getAppointments()->first();
        $route->removeWorkEvent($firstAppointment);
        unset($firstAppointment);

        $waiting = new Waiting(new TimeWindow(
            $route->getTimeWindow()->getStartAt()->clone(),
            $route->getTimeWindow()->getStartAt()->clone()->addSeconds(self::WAITING_SECONDS)
        ));
        $route->addWorkEvent($waiting);

        return $route;
    }
}
