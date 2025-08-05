<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\RouteOptimization\ValueObjects\RouteSummary;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RouteStatisticsService
{
    private Route $route;

    /**
     * Returns statistics for the route
     *
     * @param Route $route
     *
     * @return RouteStats
     */
    public function getStats(Route $route): RouteStats
    {
        $this->route = $route;

        $totalAppointments = $route->getAppointments()->count();
        $totalInitials = $this->getTotalInitialAppointments();
        $totalReservice = $this->getTotalReserviceAppointments();
        $totalRegular = $totalAppointments - $totalInitials - $totalReservice;

        return new RouteStats(
            $totalInitials,
            $totalReservice,
            $totalRegular,
            $totalAppointments,
            $this->getTotalWeightedServices(),
            $this->getTotalServiceTime(),
            $this->getTotalWorkingTime(),
            $this->getTotalBreakTime(),
            $this->getTotalDriveTime(),
            $this->getTotalDriveDistance(),
            $this->getAverageDriveTimeBetweenServices(),
            $this->getAverageDriveDistanceBetweenServices(),
            $this->getFullDriveTime(),
            $this->getFullDriveDistance(),
        );
    }

    /**
     * @param Route $route
     * @param CarbonInterface $date
     *
     * @return RouteSummary
     */
    public function getRouteSummary(Route $route, CarbonInterface $date): RouteSummary
    {
        $this->route = $route;

        return new RouteSummary(
            drivingTime: $this->getTotalDriveTime()->increase($this->getFirstAndLastTravelDuration()),
            servicingTime: $this->getTotalServiceTime(),
            totalWorkingTime: $this->getTotalWorkingTime()->decrease($this->getTotalBreakTime()),
            asOf: $date,
            excludeFirstAppointment: $this->isFirstAppointmentLocked()
        );
    }

    private function getTotalInitialAppointments(): int
    {
        $appointments = $this->route->getAppointments()->filter(
            fn (Appointment $appointment) => $appointment->isInitial()
        );

        return $appointments->count();
    }

    private function getTotalReserviceAppointments(): int
    {
        $appointments = $this->route->getAppointments()->filter(
            fn (Appointment $appointment) => $appointment->isReservice()
        );

        return $appointments->count();
    }

    /**
     * Total amount of time spent servicing appointments
     */
    private function getTotalServiceTime(): Duration
    {
        $totalDuration = Duration::fromSeconds(0);

        /** @var Appointment $appointment */
        foreach ($this->route->getAppointments() as $appointment) {
            $totalDuration = $totalDuration
                ->increase($appointment->getDuration())
                ->increase($appointment->getSetupDuration());
        }

        return $totalDuration;
    }

    /**
     * Total amount of time the service pro will work, all travel time, breaks, appointment time, etc…
     */
    private function getTotalWorkingTime(): Duration
    {
        return $this
            ->getTotalDriveTime()
            ->increase($this->getTotalServiceTime())
            ->increase($this->getTotalBreakTime());
    }

    private function getFirstAndLastTravelDuration(): Duration
    {
        /** @var Collection<Travel> $travels */
        $travels = $this->route->getTravelEvents();

        if ($travels->count() < 2) {
            return Duration::fromMinutes(0);
        }

        /** @var Travel $firstTravel */
        $firstTravel = $travels->first();

        /** @var Travel $lastTravel */
        $lastTravel = $travels->last();

        return $firstTravel->getDuration()->increase($lastTravel->getDuration());
    }

    /**
     * Total amount of time spent on breaks, including lunch
     */
    private function getTotalBreakTime(): Duration
    {
        $totalMinutes = 0;

        /** @var WorkBreak $workBreak */
        foreach ($this->route->getWorkBreaks() as $workBreak) {
            if ($workBreak->getTimeWindow()) {
                $totalMinutes += $workBreak->getTimeWindow()->getTotalMinutes();
            }
        }

        return Duration::fromMinutes($totalMinutes);
    }

    private function getTotalWeightedServices(): int
    {
        return $this->route->getAppointments()->sum(fn (Appointment $appointment) => $appointment->getWeight());
    }

    private function getTotalDriveTime(): Duration
    {
        $travels = $this->getTravelsBetweenServices();

        if ($travels->isEmpty()) {
            return Duration::fromSeconds(0);
        }

        $totalDuration = (int) $travels->sum(
            fn (Travel $travel) => $travel->getDuration()->getTotalSeconds()
        );

        return Duration::fromSeconds($totalDuration);
    }

    private function getTotalDriveDistance(): Distance
    {
        $travels = $this->getTravelsBetweenServices();

        if ($travels->isEmpty()) {
            return Distance::fromMeters(0);
        }

        $totalDistance = (float) $travels->sum(
            fn (Travel $travel) => $travel->getDistance()->getMeters()
        );

        return Distance::fromMeters($totalDistance);
    }

    private function getFullDriveTime(): Duration
    {
        $travels = $this->route->getTravelEvents();

        $totalDuration = (int) $travels->sum(
            fn (Travel $travel) => $travel->getDuration()->getTotalSeconds()
        );

        return Duration::fromSeconds($totalDuration);
    }

    private function getFullDriveDistance(): Distance
    {
        $travels = $this->route->getTravelEvents();

        $totalDistance = (float) $travels->sum(
            fn (Travel $travel) => $travel->getDistance()->getMeters()
        );

        return Distance::fromMeters($totalDistance);
    }

    private function getAverageDriveTimeBetweenServices(): Duration
    {
        $travels = $this->getTravelsBetweenServices();

        if ($travels->isEmpty()) {
            return Duration::fromSeconds(0);
        }

        $totalDurationInSeconds = $travels->sum(
            fn (Travel $travel) => $travel->getDuration()->getTotalSeconds()
        );
        $averageDuration = (int) ceil($totalDurationInSeconds / $travels->count());

        return Duration::fromSeconds($averageDuration);
    }

    private function getAverageDriveDistanceBetweenServices(): Distance
    {
        $travels = $this->getTravelsBetweenServices();

        if ($travels->isEmpty()) {
            return Distance::fromMeters(0);
        }

        $totalTravelMeters = $travels->sum(
            fn (Travel $travel) => $travel->getDistance()->getMeters()
        );
        $averageTravelMeters = $totalTravelMeters / $travels->count();

        return Distance::fromMeters($averageTravelMeters);
    }

    /**
     * @return Collection<Travel>
     */
    private function getTravelsBetweenServices(): Collection
    {
        $travels = $this->route->getTravelEvents();

        if ($travels->count() < 3) {
            return new Collection();
        }

        return $travels->slice(1, -1);
    }

    private function isFirstAppointmentLocked(): bool
    {
        return (bool) $this->route->getAppointments()->first()?->isLocked();
    }
}
