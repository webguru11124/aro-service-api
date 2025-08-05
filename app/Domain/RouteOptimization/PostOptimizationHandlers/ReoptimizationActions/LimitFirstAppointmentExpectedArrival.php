<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class LimitFirstAppointmentExpectedArrival extends AbstractReoptimizationAction
{
    private const MAX_ATTEMPTS = 1;

    protected function attempt(Route $route): Route
    {
        $servicePro = $route->getServicePro();

        /** @var Appointment $firstAppointment */
        $firstAppointment = $route->getAppointments()->first();

        /** @var Travel $firstTravel */
        $firstTravel = $route->getTravelEvents()->first();
        $firstTravelDuration = $firstTravel->getDuration();

        $possibleStartTime = $servicePro->getWorkingHours()->getStartAt()->clone()->startOfDay();
        $possibleEndTime = $servicePro->getWorkingHours()->getStartAt()->clone()
            ->addSeconds($firstTravelDuration->getTotalSeconds())
            ->addSeconds($firstAppointment->getTimeWindow()->getDuration()->getTotalSeconds());

        if ($firstAppointment->getExpectedArrival()->getStartAt() > $possibleStartTime
            || $firstAppointment->getExpectedArrival()->getEndAt() < $possibleEndTime) {
            return $route;
        }

        $firstAppointment->setExpectedArrival(new TimeWindow($possibleStartTime, $possibleEndTime));

        return $this->optimizeRoute($route);
    }

    protected function getMaxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    protected function name(): string
    {
        return 'Limit First Appointment Expected Arrival Time Window';
    }
}
