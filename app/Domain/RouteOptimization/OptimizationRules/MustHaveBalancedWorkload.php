<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use Illuminate\Support\Collection;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;

class MustHaveBalancedWorkload extends AbstractGeneralOptimizationRule
{
    /**
     * Rule to ensure that the workload is balanced between service pros
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $routes = $optimizationState->getRoutes();
        $totalAppointments = $routes->sum(fn (Route $route) => $route->getAppointments()->count());
        $totalAppointments += $optimizationState->getUnassignedAppointments()->count();

        $totalCapacity = $routes->sum(fn (Route $route) => $route->getMaxCapacity());
        $remainingAppointments = $totalAppointments;

        $this->distributeProportionalAppointments($routes, $totalAppointments, $totalCapacity, $remainingAppointments);
        $this->distributeRemainingAppointments($routes, $remainingAppointments);

        return $this->buildSuccessExecutionResult();
    }

    private function distributeProportionalAppointments(Collection $routes, int $totalAppointments, int $totalCapacity, int &$remainingAppointments): void
    {
        $routes->each(function (Route $route) use ($totalAppointments, $totalCapacity, &$remainingAppointments) {
            $proportionalCapacity = (int) floor(($route->getMaxCapacity() / $totalCapacity) * $totalAppointments);
            $allocatedCapacity = min($proportionalCapacity, $route->getMaxCapacity());
            $route->setCapacity($allocatedCapacity);
            $remainingAppointments -= $allocatedCapacity;
        });
    }

    private function distributeRemainingAppointments(Collection $routes, int &$remainingAppointments): void
    {
        $eligibleRoutes = $routes->filter(fn (Route $route) => $route->getCapacity() < $route->getMaxCapacity());

        while ($remainingAppointments > 0 && !$eligibleRoutes->isEmpty()) {
            foreach ($eligibleRoutes as $route) {
                if ($route->getCapacity() < $route->getMaxCapacity()) {
                    $currentCapacity = $route->getCapacity() + 1;
                    $route->setCapacity($currentCapacity);
                    --$remainingAppointments;

                    if ($currentCapacity >= $route->getMaxCapacity()) {
                        $eligibleRoutes = $eligibleRoutes->filter(fn (Route $eligibleRoute) => $eligibleRoute->getId() !== $route->getId());
                    }

                    if ($remainingAppointments === 0) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Balanced Workload';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule ensures that the workload is balanced between service pros, thus ensuring that each service pro is utilized as much as possible.';
    }
}
