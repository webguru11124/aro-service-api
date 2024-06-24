<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReverseRoute;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\RouteFactory;

class ReverseRouteTest extends TestCase
{
    use ProcessesOnce;

    private const DEFAUL_ENGINE = OptimizationEngine::VROOM;

    private MockInterface|RouteOptimizationService $routeOptimizationServiceMock;
    private MockInterface|RouteOptimizationServiceFactory $routeOptimizationServiceFactoryMock;
    private ReverseRoute $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeOptimizationServiceMock = Mockery::mock(RouteOptimizationService::class)
            ->makePartial();
        $this->routeOptimizationServiceFactoryMock = Mockery::mock(RouteOptimizationServiceFactory::class);
        $this->routeOptimizationServiceFactoryMock
            ->shouldReceive('getRouteOptimizationService')
            ->andReturn($this->routeOptimizationServiceMock);

        $this->action = new ReverseRoute(
            $this->routeOptimizationServiceFactoryMock
        );
    }

    /**
     * @test
     */
    public function it_reverses_route(): void
    {
        $route = $this->getTestRoute();

        $this->routeOptimizationServiceMock
            ->shouldReceive('optimizeSingleRoute')
            ->withArgs(function (Route $route) {
                /** @var Appointment $firstAppointment */
                $firstAppointment = $route->getAppointments()->first();
                /** @var Appointment $lastAppointment */
                $lastAppointment = $route->getAppointments()->last();

                return
                    $firstAppointment->getExpectedArrival()->isWholeDay()
                    && $lastAppointment->getExpectedArrival()->getEndAt()->isMidday();
            })
            ->once()
            ->andReturn($route);

        $this->action->process($route, self::DEFAUL_ENGINE);
    }

    private function getTestRoute(): Route
    {
        return RouteFactory::make([
            'workEvents' => [
                AppointmentFactory::make([
                    'expectedArrival' => new TimeWindow(
                        Carbon::tomorrow()->startOfDay(),
                        Carbon::tomorrow()->endOfDay()
                    ),
                ]),
                AppointmentFactory::make([
                    'expectedArrival' => new TimeWindow(
                        Carbon::tomorrow()->startOfDay(),
                        Carbon::tomorrow()->endOfDay()
                    ),
                ]),
            ],
        ]);
    }
}
