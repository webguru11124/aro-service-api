<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use Illuminate\Support\Collection;

class IncreaseRouteCapacity extends AbstractAdditionalOptimizationRule
{
    private const ROUTE_CAPACITY_INCREASE_VALUE = 1;

    /**
     * Increases service pro capacity by 1 for each unassigned appointment
     *
     * @param OptimizationState $sourceOptimizationState
     * @param OptimizationState $resultOptimizationState
     *
     * @return void
     */
    public function process(OptimizationState $sourceOptimizationState, OptimizationState $resultOptimizationState): void
    {
        if ($this->isSkipped($sourceOptimizationState)) {
            return;
        }

        $unassignedAppointments = $resultOptimizationState->getUnassignedAppointments()->count();
        $routes = $sourceOptimizationState->getRoutes()
            ->filter(fn (Route $route) => $route->getCapacity() < $route->getMaxCapacity())
            ->sortBy(fn (Route $route) => $route->getCapacity());

        $this->increaseCapacitiesAsNeeded($unassignedAppointments, $routes);
        $sourceOptimizationState->addRuleExecutionResults(collect([
            $this->buildSuccessExecutionResult(),
        ]));
    }

    private function increaseCapacitiesAsNeeded(int $unassignedAppointments, Collection $routes): void
    {
        /** @var Collection<Route> $routes */
        foreach ($routes as $route) {
            if ($unassignedAppointments <= 0) {
                break;
            }

            $newCapacity = min($route->getCapacity() + self::ROUTE_CAPACITY_INCREASE_VALUE, $route->getMaxCapacity());
            $route->setCapacity($newCapacity);
            $unassignedAppointments -= self::ROUTE_CAPACITY_INCREASE_VALUE;
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Increase Route Capacity';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule increases the capacity of the service pro by ' . self::ROUTE_CAPACITY_INCREASE_VALUE . ' for each unassigned appointment. This rule is applied to enable the service pro to take more appointments if they are available, thus ensuring that the appointments are not left unassigned.';
    }
}
