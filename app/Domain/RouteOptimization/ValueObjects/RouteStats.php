<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;

readonly class RouteStats
{
    public function __construct(
        private int $totalInitials,
        private int $totalReservice,
        private int $totalRegular,
        private int $totalAppointments,
        private int $totalWeightedServices,
        private Duration $totalServiceTime,
        private Duration $totalWorkingTime,
        private Duration $totalBreakTime,
        private Duration $totalDriveTime,
        private Distance $totalDriveDistance,
        private Duration $averageDriveTimeBetweenServices,
        private Distance $averageDriveDistanceBetweenServices,
        private Duration $fullDriveTime,
        private Distance $fullDriveDistance,
    ) {
    }

    /**
     * @return Duration
     */
    public function getTotalServiceTime(): Duration
    {
        return $this->totalServiceTime;
    }

    /**
     * @return Duration
     */
    public function getTotalWorkingTime(): Duration
    {
        return $this->totalWorkingTime;
    }

    /**
     * @return Duration
     */
    public function getTotalBreakTime(): Duration
    {
        return $this->totalBreakTime;
    }

    /**
     * @return int
     */
    public function getTotalInitials(): int
    {
        return $this->totalInitials;
    }

    /**
     * @return int
     */
    public function getTotalReservice(): int
    {
        return $this->totalReservice;
    }

    /**
     * @return int
     */
    public function getTotalRegular(): int
    {
        return $this->totalRegular;
    }

    /**
     * @return int
     */
    public function getTotalAppointments(): int
    {
        return $this->totalAppointments;
    }

    /**
     * @return int
     */
    public function getTotalWeightedServices(): int
    {
        return $this->totalWeightedServices;
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
     * @return Distance
     */
    public function getAverageDriveDistanceBetweenServices(): Distance
    {
        return $this->averageDriveDistanceBetweenServices;
    }

    /**
     * @return Duration
     */
    public function getAverageDriveTimeBetweenServices(): Duration
    {
        return $this->averageDriveTimeBetweenServices;
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
            'total_initials' => $this->getTotalInitials(),
            'total_reservice' => $this->getTotalReservice(),
            'total_regular' => $this->getTotalRegular(),
            'total_appointments' => $this->getTotalAppointments(),
            'total_weighted_services' => $this->getTotalWeightedServices(),
            'total_service_time_minutes' => $this->getTotalServiceTime()->getTotalMinutes(),
            'total_working_time_minutes' => $this->getTotalWorkingTime()->getTotalMinutes(),
            'total_break_time_minutes' => $this->getTotalBreakTime()->getTotalMinutes(),
            'total_drive_time_minutes' => $this->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => $this->getTotalDriveDistance()->getMiles(),
            'average_drive_time_minutes' => $this->getAverageDriveTimeBetweenServices()->getTotalMinutes(),
            'average_drive_miles' => $this->getAverageDriveDistanceBetweenServices()->getMiles(),
            'full_drive_time_minutes' => $this->getFullDriveTime()->getTotalMinutes(),
            'full_drive_miles' => $this->getFullDriveDistance()->getMiles(),
        ];
    }
}
