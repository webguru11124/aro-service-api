<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;

class LongInactivity implements RouteValidator
{
    private const VIOLATION = 'Inactivity more than 1 hour';
    private const THRESHOLD_WAITING_TIME_IN_MINUTES = 60;

    /**
     * @param Route $route
     *
     * @return bool
     */
    public function validate(Route $route): bool
    {
        $longWaiting = $route->getWaitingEvents()
            ->filter(fn (Waiting $waiting) => $waiting->getDuration()->getTotalMinutes() >= self::THRESHOLD_WAITING_TIME_IN_MINUTES);

        return $longWaiting->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public static function getViolation(): string
    {
        return self::VIOLATION;
    }
}
