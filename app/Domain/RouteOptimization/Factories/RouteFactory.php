<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;

class RouteFactory
{
    /**
     * @param mixed[] $routeData
     * @param Office $office
     * @param ServicePro $servicePro
     *
     * @return Route
     */
    public function make(array $routeData, Office $office, ServicePro $servicePro): Route
    {
        $route = new Route(
            id: $routeData['id'],
            officeId: $office->getId(),
            date: Carbon::parse($routeData['details']['start_at'], $office->getTimezone()),
            servicePro: $servicePro,
            routeType: RouteType::tryFrom($routeData['details']['route_type']),
            actualCapacityCount: $routeData['details']['actual_capacity'] ?? 0,
        );
        $route->setCapacity($routeData['details']['capacity'] ?? 0);

        return $route;
    }
}
