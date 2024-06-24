<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OptimizationStateArrayFactory
{
    public function __construct(
        private readonly OfficeQuery $officeQuery,
        private readonly RouteFactory $routeFactory,
        private readonly ServiceProFactory $serviceProFactory,
        private readonly WorkEventFactory $workEventFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $stateData
     *
     * @return OptimizationState
     */
    public function make(array $stateData): OptimizationState
    {
        $office = $this->officeQuery->get($stateData['office']['office_id']);

        $state = new OptimizationState(
            $stateData['id'],
            OptimizationEngine::tryFrom($stateData['state']['engine']),
            $stateData['status'],
            Carbon::parse($stateData['state']['created_at']),
            $office,
            new TimeWindow(
                Carbon::parse($stateData['state']['optimization_window_start']),
                Carbon::parse($stateData['state']['optimization_window_end'])
            ),
            new OptimizationParams(),
        );

        $this->resolveRoutes($state, $stateData['routes']);

        return $state;
    }

    private function resolveRoutes(OptimizationState $state, Collection $routesData): void
    {
        foreach ($routesData as $routeData) {
            $servicePro = $this->serviceProFactory->make($routeData, $state->getOffice()->getTimezone());
            $route = $this->routeFactory->make($routeData, $state->getOffice(), $servicePro);

            $this->resolveSchedule($route, $routeData['schedule'], $state->getOffice());

            $state->addRoute($route);
        }
    }

    /**
     * @param Route $route
     * @param mixed[] $schedule
     *
     * @return void
     */
    private function resolveSchedule(Route $route, array $schedule, Office $office): void
    {
        foreach ($schedule as $workEventData) {
            $workEvent = $this->workEventFactory->make($workEventData, $route, $office);
            $route->addWorkEvent($workEvent);
        }
    }
}
