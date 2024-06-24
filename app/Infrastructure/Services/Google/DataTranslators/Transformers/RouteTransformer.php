<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google\DataTranslators\Transformers;

use App\Domain\RouteOptimization\Entities\Route;
use Google\Cloud\Optimization\V1\BreakRule;
use Google\Cloud\Optimization\V1\BreakRule\BreakRequest;
use Google\Cloud\Optimization\V1\Vehicle;
use Google\Protobuf\Duration;
use Google\Protobuf\Timestamp;

class RouteTransformer
{
    /**
     * @param Route $route
     *
     * @return Vehicle
     */
    public function transform(Route $route): Vehicle
    {
        $coordinateTransformer = new CoordinateTransformer();

        $vehicle = (new Vehicle())
            ->setLabel((string) $route->getId())
            ->setStartLocation($coordinateTransformer->transform($route->getStartLocation()->getLocation()))
            ->setEndLocation($coordinateTransformer->transform($route->getEndLocation()->getLocation()));

        $this->setBreaks($route, $vehicle);

        return $vehicle;
    }

    private function setBreaks(Route $route, Vehicle $vehicle): void
    {
        if ($route->getAllBreaks()->isEmpty()) {
            return;
        }

        $breakRequests = [];

        foreach ($route->getAllBreaks() as $break) {
            $breakRequests[] = (new BreakRequest())
                ->setEarliestStartTime((new Timestamp())->setSeconds($break->getExpectedArrival()->getStartAt()->timestamp))
                ->setLatestStartTime((new Timestamp())->setSeconds($break->getExpectedArrival()->getEndAt()->timestamp))
                ->setMinDuration((new Duration())->setSeconds($break->getDuration()->getTotalSeconds()));
        }

        $breakRule = (new BreakRule())->setBreakRequests($breakRequests);
        $vehicle->setBreakRule($breakRule);
    }
}
