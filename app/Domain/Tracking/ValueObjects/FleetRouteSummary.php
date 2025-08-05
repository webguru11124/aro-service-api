<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

class FleetRouteSummary
{
    private const ROUNDING_PRECISION = 2;

    public function __construct(
        private readonly int $totalRoutes,
        private readonly int $totalAppointments,
        private readonly int $totalDriveTimeMinutes,
        private readonly float $totalDriveMiles,
        private readonly int $totalServiceTimeMinutes,
        private readonly float $appointmentsPerGallon,
        private readonly int $totalRoutesActual,
        private readonly int $totalAppointmentsActual,
        private readonly int $totalDriveTimeMinutesActual,
        private readonly float $totalDriveMilesActual,
        private readonly int $totalServiceTimeMinutesActual,
        private readonly float $appointmentsPerGallonActual
    ) {
    }

    /**
     * Get the total number of routes
     *
     * @return int
     */
    public function getTotalRoutes(): int
    {
        return $this->totalRoutes;
    }

    /**
     * Get the total number of appointments
     *
     * @return int
     */
    public function getTotalAppointments(): int
    {
        return $this->totalAppointments;
    }

    /**
     * Get the total drive time in minutes
     *
     * @return int
     */
    public function getTotalDriveTimeMinutes(): int
    {
        return $this->totalDriveTimeMinutes;
    }

    /**
     * Get the total drive miles
     *
     * @return float
     */
    public function getTotalDriveMiles(): float
    {
        return $this->totalDriveMiles;
    }

    /**
     * Get the total service time in minutes
     *
     * @return int
     */
    public function getTotalServiceTimeMinutes(): int
    {
        return $this->totalServiceTimeMinutes;
    }

    /**
     * Get the appointments per gallon
     *
     * @return float
     */
    public function getAppointmentsPerGallon(): float
    {
        return $this->appointmentsPerGallon;
    }

    /**
     * Get the total number of actual routes
     *
     * @return int
     */
    public function getTotalRoutesActual(): int
    {
        return $this->totalRoutesActual;
    }

    /**
     * Get the total number of actual appointments
     *
     * @return int
     */
    public function getTotalAppointmentsActual(): int
    {
        return $this->totalAppointmentsActual;
    }

    /**
     * Get the actual total drive time in minutes
     *
     * @return int
     */
    public function getTotalDriveTimeMinutesActual(): int
    {
        return $this->totalDriveTimeMinutesActual;
    }

    /**
     * Get the actual total drive miles
     *
     * @return float
     */
    public function getTotalDriveMilesActual(): float
    {
        return $this->totalDriveMilesActual;
    }

    /**
     * Get the actual total service time in minutes
     *
     * @return int
     */
    public function getTotalServiceTimeMinutesActual(): int
    {
        return $this->totalServiceTimeMinutesActual;
    }

    /**
     * Get the actual appointments per gallon
     *
     * @return float
     */
    public function getAppointmentsPerGallonActual(): float
    {
        return $this->appointmentsPerGallonActual;
    }

    /**
     * Formats fleet route summary as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_routes' => $this->getTotalRoutes(),
            'total_appointments' => $this->getTotalAppointments(),
            'total_drive_time_minutes' => $this->getTotalDriveTimeMinutes(),
            'total_drive_miles' => round($this->getTotalDriveMiles(), self::ROUNDING_PRECISION),
            'total_service_time_minutes' => $this->getTotalServiceTimeMinutes(),
            'appointments_per_gallon' => round($this->getAppointmentsPerGallon(), self::ROUNDING_PRECISION),
            'total_routes_actual' => $this->getTotalRoutesActual(),
            'total_appointments_actual' => $this->getTotalAppointmentsActual(),
            'total_drive_time_minutes_actual' => $this->getTotalDriveTimeMinutesActual(),
            'total_drive_miles_actual' => round($this->getTotalDriveMilesActual(), self::ROUNDING_PRECISION),
            'total_service_time_minutes_actual' => $this->getTotalServiceTimeMinutesActual(),
            'appointments_per_gallon_actual' => round($this->getAppointmentsPerGallonActual(), self::ROUNDING_PRECISION),
        ];
    }
}
