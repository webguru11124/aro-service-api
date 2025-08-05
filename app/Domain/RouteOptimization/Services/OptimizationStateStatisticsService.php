<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\OptimizationStateStats;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Illuminate\Support\Collection;

class OptimizationStateStatisticsService
{
    private const SECONDS_IN_HOUR = 3600;

    private OptimizationState $state;

    /** @var Collection<RouteStats> */
    private Collection $routesStat;

    public function __construct(
        private RouteStatisticsService $routeStatisticsService,
    ) {
    }

    /**
     * Returns statistics of the OptimizationState
     *
     * @param OptimizationState $state
     *
     * @return OptimizationStateStats
     */
    public function getStats(OptimizationState $state): OptimizationStateStats
    {
        $this->state = $state;

        $this->resolveRoutesStats();

        return new OptimizationStateStats(
            $this->getTotalAssignedAppointments(),
            $this->getTotalUnassignedAppointments(),
            $this->state->getRoutes()->count(),
            $this->getTotalDriveTime(),
            $this->getTotalDriveDistance(),
            $this->getServicesPerHour(),
            $this->getAverageDailyWorkingHours(),
            $this->getFullDriveTime(),
            $this->getFullDriveDistance(),
        );
    }

    private function getTotalAssignedAppointments(): int
    {
        return $this->routesStat->sum(fn (RouteStats $routeStats) => $routeStats->getTotalAppointments());
    }

    private function getTotalUnassignedAppointments(): int
    {
        return $this->state->getUnassignedAppointments()->count();
    }

    private function getTotalDriveTime(): Duration
    {
        $totalTime = $this->routesStat->sum(
            fn (RouteStats $routeStats) => $routeStats->getTotalDriveTime()->getTotalSeconds()
        );

        return Duration::fromSeconds($totalTime);
    }

    private function getTotalDriveDistance(): Distance
    {
        $totalMeters = $this->routesStat->sum(
            fn (RouteStats $routeStats) => $routeStats->getTotalDriveDistance()->getMeters()
        );

        return Distance::fromMeters($totalMeters);
    }

    private function getFullDriveTime(): Duration
    {
        $totalTime = $this->routesStat->sum(
            fn (RouteStats $routeStats) => $routeStats->getFullDriveTime()->getTotalSeconds()
        );

        return Duration::fromSeconds($totalTime);
    }

    private function getFullDriveDistance(): Distance
    {
        $totalMeters = $this->routesStat->sum(
            fn (RouteStats $routeStats) => $routeStats->getFullDriveDistance()->getMeters()
        );

        return Distance::fromMeters($totalMeters);
    }

    private function getAverageDailyWorkingHours(): float
    {
        $validRoutes = $this->routesStat->filter(
            fn (RouteStats $routeStats) => $routeStats->getTotalWorkingTime()->getTotalSeconds() > 0
        );

        $totalWorkingSeconds = $validRoutes->sum(
            fn (RouteStats $routeStats) => $routeStats->getTotalWorkingTime()->getTotalSeconds()
        );

        $validRouteCount = $validRoutes->count();
        $totalWorkingHours = $totalWorkingSeconds / self::SECONDS_IN_HOUR;

        return $validRouteCount ? round($totalWorkingHours / $validRouteCount, 2) : 0.0;
    }

    private function getServicesPerHour(): float
    {
        $totalServiceTime = $this->routesStat->sum(
            fn (RouteStats $routeStats) => $routeStats->getTotalServiceTime()->getTotalSeconds()
        );

        $totalHours = $totalServiceTime / self::SECONDS_IN_HOUR;

        return $totalHours > 0 ? round($this->getTotalAssignedAppointments() / $totalHours, 2) : 0.0;
    }

    private function resolveRoutesStats(): void
    {
        $this->routesStat = new Collection();

        /** @var Route $route */
        foreach ($this->state->getRoutes() as $route) {
            $this->routesStat->add($this->routeStatisticsService->getStats($route));
        }
    }
}
