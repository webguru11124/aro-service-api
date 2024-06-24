<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\TestValue;

trait TranslatorHelpers
{
    private function buildRoute(array $workEvents = []): Route
    {
        return RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'officeId' => $this->office->getId(),
            'servicePro' => $this->buildServicePro(),
            'workEvents' => $workEvents,
        ]);
    }

    private function buildServicePro(): ServicePro
    {
        return ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::today($this->office->getTimeZone())->setTimeFromTimeString('08:00:00'),
                Carbon::today($this->office->getTimeZone())->setTimeFromTimeString('18:00:00'),
            ),
        ]);
    }

    private function buildSourceOptimizationState(array $routes = [], array $unassigned = []): OptimizationState
    {
        return TestOptimizationStateFactory::make([
            'engine' => self::OPTIMIZATION_ENGINE,
            'id' => TestValue::OPTIMIZATION_STATE_ID,
            'officeId' => $this->office->getId(),
            'routes' => $routes,
            'status' => OptimizationStatus::POST,
            'unassignedAppointments' => $unassigned,
        ]);
    }

    private function setMockOptimizationStateFactoryExpectations(OptimizationState $state): void
    {
        $this->mockOptimizationStateFactory->shouldReceive('makeFromOptimizationState')
            ->once()
            ->withArgs(
                function (OptimizationState $priorState, OptimizationEngine $optimizationEngine, OptimizationStatus $status) {
                    return $optimizationEngine == self::OPTIMIZATION_ENGINE
                        && $status == OptimizationStatus::POST
                        && $priorState->getId() == TestValue::OPTIMIZATION_STATE_ID;
                }
            )
            ->andReturn($state);
    }
}
