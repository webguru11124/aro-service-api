<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitBreakTimeFrames;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\LimitFirstAppointmentExpectedArrival;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReduceWorkTimeRange;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReoptimizationActionFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions\ReverseRoute;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\AverageInactivity;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\InactivityBeforeFirstAppointment;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\LongInactivity;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\RouteValidatorsRegister;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\TwoBreaksInARow;
use Illuminate\Support\Facades\Log;

class ReoptimizeRoutes implements PostOptimizationHandler
{
    private OptimizationEngine $engine;

    public function __construct(
        private readonly ReoptimizationActionFactory $reoptimizationActionFactory,
        private readonly RouteValidatorsRegister $routeValidatorsRegister
    ) {
    }

    public function process(OptimizationState $optimizationState): void
    {
        $this->engine = $optimizationState->getOptimizationEngine();

        foreach ($optimizationState->getRoutes() as $route) {
            $newRoute = $this->processRoute($route);

            $optimizationState->updateRoute($newRoute);
        }
    }

    private function processRoute(Route $route, int $attempt = 1): Route
    {
        if ($route->getAppointments()->isEmpty()) {
            return $route;
        }

        $violations = [];

        foreach ($this->routeValidatorsRegister->getValidators() as $validator) {
            if (!$validator->validate($route)) {
                $violations[] = $validator->getViolation();
            }
        }

        if (empty($violations)) {
            return $route;
        }

        $reoptimizeMethodName = 'reoptimizeAttempt' . $attempt;
        if (!method_exists($this, $reoptimizeMethodName)) {
            return $route;
        }

        Log::info(__('messages.routes_optimization.start_reoptimization', [
            'route_id' => $route->getId(),
            'office_id' => $route->getOfficeId(),
            'attempt' => $attempt,
        ]));

        $this->enforceMaxLoad($route);

        $reoptimizedRoute = $this->$reoptimizeMethodName($route, $violations);

        return $this->processRoute($reoptimizedRoute, ++$attempt);
    }

    /**
     * @param Route $route
     * @param class-string[] $violations
     *
     * @return Route
     */
    private function reoptimizeAttempt1(Route $route, array $violations): Route
    {
        if (in_array(LongInactivity::getViolation(), $violations)) {
            return $this->processAction(ReverseRoute::class, $route);
        }

        if (in_array(AverageInactivity::getViolation(), $violations)) {
            return $this->processAction(ReduceWorkTimeRange::class, $route);
        }

        if (in_array(TwoBreaksInARow::getViolation(), $violations)) {
            return $this->processAction(LimitBreakTimeFrames::class, $route);
        }

        return $route;
    }

    /**
     * @param Route $route
     * @param class-string[] $violations
     *
     * @return Route
     */
    private function reoptimizeAttempt2(Route $route, array $violations): Route
    {
        if (in_array(AverageInactivity::getViolation(), $violations)) {
            return $this->processAction(ReduceWorkTimeRange::class, $route);
        }

        if (in_array(TwoBreaksInARow::getViolation(), $violations)) {
            return $this->processAction(LimitBreakTimeFrames::class, $route);
        }

        return $route;
    }

    /**
     * @param Route $route
     * @param class-string[] $violations
     *
     * @return Route
     */
    private function reoptimizeAttempt3(Route $route, array $violations): Route
    {
        if (in_array(InactivityBeforeFirstAppointment::getViolation(), $violations)) {
            return $this->processAction(LimitFirstAppointmentExpectedArrival::class, $route);
        }

        if (in_array(TwoBreaksInARow::getViolation(), $violations)) {
            return $this->processAction(LimitBreakTimeFrames::class, $route);
        }

        return $route;
    }

    /**
     * @param Route $route
     * @param class-string[] $violations
     *
     * @return Route
     */
    private function reoptimizeAttempt4(Route $route, array $violations): Route
    {
        if (in_array(TwoBreaksInARow::getViolation(), $violations)) {
            return $this->processAction(LimitBreakTimeFrames::class, $route);
        }

        return $route;
    }

    private function processAction(string $actionClass, Route $route): Route
    {
        return $this->reoptimizationActionFactory->getAction($actionClass)->process($route, $this->engine);
    }

    private function enforceMaxLoad(Route $route): void
    {
        $actualAppointmentsBeforeBreak = [];
        $breakIndex = 0;
        foreach ($route->getWorkEvents() as $workEvent) {
            if (!isset($actualAppointmentsBeforeBreak[$breakIndex])) {
                $actualAppointmentsBeforeBreak[$breakIndex] = 0;
            }

            if ($workEvent instanceof Appointment) {
                $actualAppointmentsBeforeBreak[$breakIndex]++;
            }

            if ($workEvent instanceof WorkBreak) {
                $actualAppointmentsBeforeBreak[++$breakIndex] = $actualAppointmentsBeforeBreak[$breakIndex - 1];
            }
        }

        $previous = 0;
        foreach ($route->getWorkBreaks() as $idx => $break) {
            if ($actualAppointmentsBeforeBreak[$idx] === $previous) {
                array_walk($actualAppointmentsBeforeBreak, fn (int & $item) => ++$item);
            }
            $break->setMinAppointmentsBefore($actualAppointmentsBeforeBreak[$idx]);
            $previous = $actualAppointmentsBeforeBreak[$idx];
        }
    }
}
