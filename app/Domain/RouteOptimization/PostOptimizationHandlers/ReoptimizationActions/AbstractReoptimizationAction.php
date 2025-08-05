<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Exceptions\UnknownRouteOptimizationEngineIdentifier;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use Illuminate\Support\Facades\Log;

abstract class AbstractReoptimizationAction
{
    /** @var array<int, int>  */
    private array $attemptsCounter = [];
    private OptimizationEngine $engine;

    public function __construct(
        private readonly RouteOptimizationServiceFactory $routeOptimizationServiceFactory,
    ) {
    }

    /**
     * @param Route $route
     *
     * @return Route
     */
    public function process(Route $route, OptimizationEngine $engine): Route
    {
        $this->engine = $engine;

        if ($this->getRouteAttempts($route) >= $this->getMaxAttempts()) {
            return $route;
        }

        $this->registerAttempt($route);

        // TODO: Review this logic, it should return original route if reoptimization is not possible after all attempts (AARO-858)
        $initialRoute = $this->isLastAttempt($route) ? clone $route : $route;

        Log::info(__('messages.routes_optimization.run_reoptimization', [
            'route_id' => $initialRoute->getId(),
            'office_id' => $initialRoute->getOfficeId(),
            'rule' => $this->name(),
            'attempt' => $this->attemptsCounter[$route->getId()],
        ]));

        $reoptimizedRoute = $this->attempt($initialRoute);

        return $this->reoptimizationSuccessfull($initialRoute, $reoptimizedRoute)
            ? $reoptimizedRoute
            : $initialRoute;
    }

    protected function removeInconsistentBreaks(Route $route): Route
    {
        $workEnd = $route->getTimeWindow()->getEndAt();

        /** @var WorkBreak|ReservedTime $break */
        foreach ($route->getAllBreaks() as $break) {
            $minimalBreakEnd = $break
                ->getExpectedArrival()
                ->getStartAt()
                ->clone()
                ->addSeconds($break->getDuration()->getTotalSeconds());

            $estimatedEndAt = $break->getExpectedArrival()->getEndAt();

            if (max($estimatedEndAt, $minimalBreakEnd) > $workEnd) {
                $route->removeWorkEvent($break);
            }
        }

        return $route;
    }

    private function isLastAttempt(Route $route): bool
    {
        return $this->getRouteAttempts($route) === $this->getMaxAttempts();
    }

    private function getRouteAttempts(Route $route): int
    {
        return $this->attemptsCounter[$route->getId()] ?? 0;
    }

    private function registerAttempt(Route $route): void
    {
        $currentCount = $this->getRouteAttempts($route);

        $this->attemptsCounter[$route->getId()] = ++$currentCount;
    }

    /**
     * Determines if a reoptimized route is not worse than initial
     */
    private function reoptimizationSuccessfull(Route $initialRoute, Route $reoptimizedRoute): bool
    {
        $isUnassignedAppeared = $reoptimizedRoute->getAppointments()->count() < $initialRoute->getAppointments()->count();
        $isLongerDuration = $reoptimizedRoute->getEndLocation()->getTimeWindow()->getEndAt() > $initialRoute->getTimeWindow()->getEndAt();

        if ($isUnassignedAppeared || $isLongerDuration) {
            Log::info(__('messages.routes_optimization.reoptimization_failed', [
                'route_id' => $initialRoute->getId(),
                'office_id' => $initialRoute->getOfficeId(),
                'date' => $initialRoute->getDate()->toDateString(),
            ]));

            return false;
        }

        return true;
    }

    /**
     * @throws UnknownRouteOptimizationEngineIdentifier
     */
    protected function optimizeRoute(Route $route): Route
    {
        return $this->routeOptimizationServiceFactory
            ->getRouteOptimizationService($this->engine)
            ->optimizeSingleRoute($route);
    }

    /**
     * @throws UnknownRouteOptimizationEngineIdentifier
     */
    abstract protected function attempt(Route $route): Route;

    /**
     * @return int
     */
    abstract protected function getMaxAttempts(): int;

    abstract protected function name(): string;
}
