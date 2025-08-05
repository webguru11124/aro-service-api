<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Exceptions\UnknownRouteOptimizationEngineIdentifier;

class RouteOptimizationServiceFactory
{
    /** @var RouteOptimizationService[]  */
    private array $routeOptimizationServices;

    public function __construct(
        RouteOptimizationService ...$routeOptimizationServices
    ) {
        $this->routeOptimizationServices = $routeOptimizationServices;
    }

    /**
     * Get route optimization service by engine identifier
     *
     * @throws UnknownRouteOptimizationEngineIdentifier
     */
    public function getRouteOptimizationService(OptimizationEngine $engine): RouteOptimizationService
    {
        foreach ($this->routeOptimizationServices as $routeOptimizationService) {
            if ($routeOptimizationService->getIdentifier() === $engine) {

                return $routeOptimizationService;
            }
        }

        throw UnknownRouteOptimizationEngineIdentifier::instance($engine->value);
    }
}
