<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;

class InactivityBeforeFirstAppointment implements RouteValidator
{
    private const VIOLATION = 'Inactivity at day start more than 20 minutes';

    private const THRESHOLD_WAITING_TIME_IN_MINUTES = 20;

    /**
     * @param Route $route
     *
     * @return bool
     */
    public function validate(Route $route): bool
    {
        /** @var Waiting $longWaiting */
        $longWaiting = $route->getWaitingEvents()
            ->filter(fn (Waiting $waiting) => $waiting->getDuration()->getTotalMinutes() >= self::THRESHOLD_WAITING_TIME_IN_MINUTES)
            ->first();

        if ($longWaiting === null) {
            return true;
        }

        /** @var Appointment $firstAppointment */
        $firstAppointment = $route->getAppointments()->first();

        return $longWaiting->getTimeWindow()->getStartAt() > $firstAppointment->getTimeWindow()->getStartAt();
    }

    /**
     * @inheritDoc
     */
    public static function getViolation(): string
    {
        return self::VIOLATION;
    }
}
