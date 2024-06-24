<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

readonly class OptimizationStateMetrics
{
    public function __construct(
        private float|null $totalDriveTime = null,
        private float|null $totalDriveMiles = null,
        private float|null $optimizationScore = null,
        private float|null $totalWorkingHours = null,
        private float|null $totalWeightedServices = null,
        private float|null $averageTimeBetweenServices = null,
        private float|null $averageMilesBetweenServices = null,
        private float|null $averageWeightedServicesPerHour = null,
    ) {
    }

    /**
     * @return float|null
     */
    public function getTotalDriveTime(): float|null
    {
        return $this->totalDriveTime;
    }

    /**
     * @return float|null
     */
    public function getTotalDriveMiles(): float|null
    {
        return $this->totalDriveMiles;
    }

    /**
     * @return float|null
     */
    public function getOptimizationScore(): float|null
    {
        return $this->optimizationScore;
    }

    /**
     * @return float|null
     */
    public function getTotalWorkingHours(): float|null
    {
        return $this->totalWorkingHours;
    }

    /**
     * @return float|null
     */
    public function getTotalWeightedServices(): float|null
    {
        return $this->totalWeightedServices;
    }

    /**
     * @return float|null
     */
    public function getAverageTimeBetweenServices(): float|null
    {
        return $this->averageTimeBetweenServices;
    }

    /**
     * @return float|null
     */
    public function getAverageMilesBetweenServices(): float|null
    {
        return $this->averageMilesBetweenServices;
    }

    /**
     * @return float|null
     */
    public function getAverageWeightedServicesPerHour(): float|null
    {
        return $this->averageWeightedServicesPerHour;
    }
}
