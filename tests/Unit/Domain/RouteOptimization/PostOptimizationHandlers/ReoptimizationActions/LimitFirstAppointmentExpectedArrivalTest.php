<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitFirstAppointmentExpectedArrival;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\TravelFactory;

class LimitFirstAppointmentExpectedArrivalTest extends TestCase
{
    use ProcessesOnce;

    private const DEFAUL_ENGINE = OptimizationEngine::VROOM;
    private const TRAVEL_SECONDS = 1200;
    private const FIRST_APPOINTMENT_DURATION_SECONDS = 1200;

    private MockInterface|RouteOptimizationService $routeOptimizationServiceMock;
    private MockInterface|RouteOptimizationServiceFactory $routeOptimizationServiceFactoryMock;
    private LimitFirstAppointmentExpectedArrival $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeOptimizationServiceMock = Mockery::mock(RouteOptimizationService::class)
            ->makePartial();
        $this->routeOptimizationServiceFactoryMock = Mockery::mock(RouteOptimizationServiceFactory::class);
        $this->routeOptimizationServiceFactoryMock
            ->shouldReceive('getRouteOptimizationService')
            ->andReturn($this->routeOptimizationServiceMock);

        $this->action = new LimitFirstAppointmentExpectedArrival(
            $this->routeOptimizationServiceFactoryMock
        );
    }

    /**
     * @test
     */
    public function it_limits_first_appointment_expected_arrival(): void
    {
        /** @var Route $route */
        $route = $this->getTestRoute();

        $this->routeOptimizationServiceMock
            ->shouldReceive('optimizeSingleRoute')
            ->withArgs(function (Route $route) {
                /** @var Appointment $firstAppointment */
                $firstAppointment = $route->getAppointments()->first();

                $expectedStartTime = Carbon::tomorrow()->startOfDay();
                $expectedEndTime = $route->getServicePro()
                    ->getWorkingHours()
                    ->getStartAt()
                    ->clone()
                    ->addSeconds(self::TRAVEL_SECONDS + self::FIRST_APPOINTMENT_DURATION_SECONDS);

                return
                    $firstAppointment->getExpectedArrival()->getStartAt() == $expectedStartTime
                    && $firstAppointment->getExpectedArrival()->getEndAt() == $expectedEndTime;
            })
            ->once()
            ->andReturn($route);

        $this->action->process($route, self::DEFAUL_ENGINE);
    }

    private function getTestRoute(): Route
    {
        return RouteFactory::make([
            'workEvents' => [
                TravelFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::tomorrow()->hour(8),
                        Carbon::tomorrow()->hour(8)->addSeconds(self::TRAVEL_SECONDS),
                    ),
                ]),
                AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::tomorrow()->hour(9),
                        Carbon::tomorrow()->hour(9)->addSeconds(self::FIRST_APPOINTMENT_DURATION_SECONDS),
                    ),
                    'expectedArrival' => new TimeWindow(
                        Carbon::tomorrow()->startOfDay(),
                        Carbon::tomorrow()->endOfDay()
                    ),
                ]),
                AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        Carbon::tomorrow()->hour(10),
                        Carbon::tomorrow()->hour(10)->minute(30),
                    ),
                    'expectedArrival' => new TimeWindow(
                        Carbon::tomorrow()->startOfDay(),
                        Carbon::tomorrow()->endOfDay()
                    ),
                ]),
            ],
        ]);
    }
}
