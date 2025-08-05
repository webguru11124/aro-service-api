<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;

readonly class RouteDrivingStats
{
    public function __construct(
        private string $id,
        private Duration $totalDriveTime,
        private Distance $totalDriveDistance,
        private Duration $averageDriveTimeBetweenServices,
        private Distance $averageDriveDistanceBetweenServices,
        private Duration $totalWorkingTime,
        private float $fuelConsumption,
        private Distance $historicVehicleMileage,
        private float $historicFuelConsumption,
    ) {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
    public function getTotalWorkingTime(): Duration
    {
        return $this->totalWorkingTime;
    }

    /**
     * @return float
     */
    public function getFuelConsumption(): float
    {
        return $this->fuelConsumption;
    }

    /**
     * @return Distance
     */
    public function getHistoricVehicleMileage(): Distance
    {
        return $this->historicVehicleMileage;
    }

    /**
     * @return float
     */
    public function getHistoricFuelConsumption(): float
    {
        return $this->historicFuelConsumption;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_drive_time_minutes' => $this->getTotalDriveTime()->getTotalMinutes(),
            'total_drive_miles' => $this->getTotalDriveDistance()->getMiles(),
            'average_drive_time_minutes' => $this->getAverageDriveTimeBetweenServices()->getTotalMinutes(),
            'average_drive_miles' => $this->getAverageDriveDistanceBetweenServices()->getMiles(),
            'total_working_time_minutes' => $this->getTotalWorkingTime()->getTotalMinutes(),
            'fuel_consumption' => $this->getFuelConsumption(),
            'historic_vehicle_mileage' => $this->getHistoricVehicleMileage()->getMiles(),
            'historic_fuel_consumption' => $this->getHistoricFuelConsumption(),
        ];
    }
}
