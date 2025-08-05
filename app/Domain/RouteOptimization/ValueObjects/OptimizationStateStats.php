<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;

readonly class OptimizationStateStats
{
    public function __construct(
        private int $totalAssignedAppointments,
        private int $totalUnassignedAppointments,
        private int $totalRoutes,
        private Duration $totalDriveTime,
        private Distance $totalDriveDistance,
        private float $servicesPerHour,
        private float $averageDailyWorkingHours,
        private Duration $fullDriveTime,
        private Distance $fullDriveDistance,
    ) {
    }

    /**
     * @return Duration
     */
    public function getTotalDriveTime(): Duration
    {
        return $this->totalDriveTime;
    }

    /**
     * @return Distance
     */
    public function getTotalDriveDistance(): Distance
    {
        return $this->totalDriveDistance;
    }

    /**
     * @return float
     */
    public function getServicesPerHour(): float
    {
        return $this->servicesPerHour;
    }

    /**
     * @return float
     */
    public function getAverageDailyWorkingHours(): float
    {
        return $this->averageDailyWorkingHours;
    }

    /**
     * @return int
     */
    public function getTotalAssignedAppointments(): int
    {
        return $this->totalAssignedAppointments;
    }

    /**
     * @return int
     */
    public function getTotalUnassignedAppointments(): int
    {
        return $this->totalUnassignedAppointments;
    }

    /**
     * @return int
     */
    public function getTotalRoutes(): int
    {
        return $this->totalRoutes;
    }

    /**
     * @return Duration
     */
    public function getFullDriveTime(): Duration
    {
        return $this->fullDriveTime;
    }

    /**
     * @return Distance
     */
    public function getFullDriveDistance(): Distance
    {
        return $this->fullDriveDistance;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_assigned_appointments' => $this->getTotalAssignedAppointments(),
            'total_unassigned_appointments' => $this->getTotalUnassignedAppointments(),
            'total_routes' => $this->getTotalRoutes(),
            'total_drive_time' => $this->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => $this->getTotalDriveDistance()->getMiles(),
            'services_per_hour' => $this->getServicesPerHour(),
            'average_daily_working_hours' => $this->getAverageDailyWorkingHours(),
            'full_drive_time' => $this->getFullDriveTime()->getTotalMinutes(),
            'full_drive_miles' => $this->getFullDriveDistance()->getMiles(),
        ];
    }
}
