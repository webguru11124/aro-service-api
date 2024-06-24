<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Contracts\OptimizationRule;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Infrastructure\Exceptions\VroomErrorResponseException;
use Illuminate\Support\Collection;

interface RouteOptimizationService
{
    /**
     * Optimizes a given array of routes and returns the optimized routes
     *
     * @param OptimizationState $sourceState
     * @param Collection<OptimizationRule> $rules
     *
     * @return OptimizationState
     */
    public function optimize(OptimizationState $sourceState, Collection $rules): OptimizationState;

    /**
     * @param Route $route
     *
     * @return Route
     */
    public function optimizeSingleRoute(Route $route): Route;

    /**
     * Processes OptimizationState and returns the planned routes with drive distance and time
     *
     * @param OptimizationState $sourceData
     *
     * @return OptimizationState
     * @throws VroomErrorResponseException
     */
    public function plan(OptimizationState $sourceData): OptimizationState;

    /**
     * Returns the identifier of the optimization engine
     */
    public function getIdentifier(): OptimizationEngine;
}
