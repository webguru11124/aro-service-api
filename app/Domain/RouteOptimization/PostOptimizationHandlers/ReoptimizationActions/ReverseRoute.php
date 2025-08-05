<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class ReverseRoute extends AbstractReoptimizationAction
{
    private const MAX_ATTEMPTS = 1;

    protected function attempt(Route $route): Route
    {
        foreach ($route->getAppointments()->reverse() as $appointment) {
            if ($appointment->getExpectedArrival()?->isWholeDay()) {
                $day = $appointment->getExpectedArrival()->getStartAt()->clone();
                // set last AT appointment to AM
                $appointment->setExpectedArrival(
                    new TimeWindow($day->startOfDay(), $day->clone()->midDay())
                );

                break;
            }
        }

        return $this->optimizeRoute($route);
    }

    protected function getMaxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    protected function name(): string
    {
        return 'Reverse Route';
    }
}
