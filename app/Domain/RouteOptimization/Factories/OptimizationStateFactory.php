<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OptimizationStateFactory
{
    public function __construct(
        private readonly OptimizationStateRepository $optimizationStateRepository,
    ) {
    }

    public function makeFromOptimizationState(
        OptimizationState $priorState,
        OptimizationEngine $optimizationEngine,
        OptimizationStatus $status,
    ): OptimizationState {
        if ($status === OptimizationStatus::POST && $priorState->getOptimizationParams()->simulationRun) {
            $status = OptimizationStatus::SIMULATION;
        }

        $optimizationState = new OptimizationState(
            $this->optimizationStateRepository->getNextId(),
            $optimizationEngine,
            $status,
            Carbon::now(),
            $priorState->getOffice(),
            $priorState->getOptimizationTimeFrame(),
            $priorState->getOptimizationParams(),
        );
        $this->transferDataBetweenStates($optimizationState, $priorState);

        return $optimizationState;
    }

    /**
     * @param OptimizationState $priorState
     *
     * @return Route[]
     */
    private function clonedRoutes(OptimizationState $priorState): array
    {
        $clonedRoutes = [];
        $priorRoutes = $priorState->getRoutes();
        foreach ($priorRoutes as $priorRoute) {
            $newRoute = new Route(
                id: $priorRoute->getId(),
                officeId: $priorState->getOffice()->getId(),
                date: $priorRoute->getDate()->clone(),
                servicePro: $priorRoute->getServicePro(),
                routeType: $priorRoute->getRouteType(),
                actualCapacityCount: $priorRoute->getActualCapacityCount(),
                config: $priorRoute->getConfig(),
            );
            $newRoute->addWorkEvents($this->clonedWorkEvents($priorRoute->getWorkEvents()));
            $newRoute->setCapacity($priorRoute->getCapacity());

            // TODO: Think of a better approach to store evaluated working hours for service pro
            $newRoute->getServicePro()->setWorkingHours($priorRoute->getTimeWindow());

            $clonedRoutes[] = $newRoute;
        }

        return $clonedRoutes;
    }

    private function clonedWorkEvents(Collection $workEvents): Collection
    {
        $cloned = new Collection();
        foreach ($workEvents as $workEvent) {
            $cloned->add(clone $workEvent);
        }

        return $cloned;
    }

    private function transferDataBetweenStates(OptimizationState $newState, OptimizationState $priorState): void
    {
        $newState
            ->addRoute(...$this->clonedRoutes($priorState))
            ->addUnassignedAppointment(...$this->clonedWorkEvents($priorState->getUnassignedAppointments()))
            ->setPreviousStateId($priorState->getId())
            ->addRuleExecutionResults($priorState->getRuleExecutionResults())
            ->setWeatherInfo($priorState->getWeatherInfo());
    }
}
