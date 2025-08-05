<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;

class ServiceDuration
{
    private const LINEAR_FOOT_PER_SECOND = 1.45;
    private float $perimeter;

    public function __construct(
        private readonly PropertyDetails $propertyDetails,
        private float|null $historicalAppointmentAverageDuration = null,
        private readonly WeatherInfo|null $weatherInfo = null,
        private float|null $customLF = null,
    ) {
        $this->perimeter = $this->calculatePerimeter();
    }

    /**
     * Get the minimum duration.
     *
     * @return Duration
     */
    public function getMinimumDuration(): Duration
    {
        if ($this->customLF) {
            $linearFootPerSecond = $this->customLF;
        } elseif ($this->historicalAppointmentAverageDuration) {
            $linearFootPerSecond = (60 * $this->historicalAppointmentAverageDuration - 360) / $this->perimeter;
        } else {
            $linearFootPerSecond = self::LINEAR_FOOT_PER_SECOND;
        }

        $minimumDurationSeconds = 360 + $this->getLinearFootPerSecond($linearFootPerSecond) * $this->perimeter;

        return Duration::fromSeconds((int) $minimumDurationSeconds);
    }

    /**
     * Get the maximum duration.
     *
     * @return Duration
     */
    public function getMaximumDuration(): Duration
    {
        if ($this->customLF) {
            $linearFootPerSecond = $this->customLF;
        } elseif ($this->historicalAppointmentAverageDuration) {
            $linearFootPerSecond = pow(
                (60 * $this->historicalAppointmentAverageDuration - 360) / $this->perimeter,
                1 / 2.5
            );
        } else {
            $linearFootPerSecond = self::LINEAR_FOOT_PER_SECOND;
        }

        $maximumDurationSeconds = 360 + pow($this->getLinearFootPerSecond($linearFootPerSecond), 2.5) * $this->perimeter;

        return Duration::fromSeconds((int) $maximumDurationSeconds);
    }

    /**
     * Get the optimum duration.
     *
     * @return Duration
     */
    public function getOptimumDuration(): Duration
    {
        if ($this->customLF) {
            $linearFootPerSecond = $this->customLF;
        } elseif ($this->historicalAppointmentAverageDuration) {
            $linearFootPerSecond = $this->calculateLFforOptimumDuration();
        } else {
            $linearFootPerSecond = self::LINEAR_FOOT_PER_SECOND;
        }

        $linearFootPerSecond = $this->getLinearFootPerSecond($linearFootPerSecond);
        $minimum = $linearFootPerSecond * $this->perimeter;
        $maximum = pow($linearFootPerSecond, 2.5) * $this->perimeter;

        $optimumDurationSeconds = (360 + ($minimum + $maximum) / 2);

        return Duration::fromSeconds((int) $optimumDurationSeconds);
    }

    /**
     * Get the linear foot per second for the optimum duration.
     *
     * @return float|null
     */
    public function getLFforOptimumDuration(): float|null
    {
        if ($this->historicalAppointmentAverageDuration) {
            return $this->calculateLFforOptimumDuration();
        } else {
            return null;
        }
    }

    private function calculateLFforOptimumDuration(): float
    {
        $a = 0;
        $b = 10;
        $tolerance = 0.01;
        $maxIterations = 1000;

        $functionValue = function ($x) {
            return (pow($x, 2.5) * $this->perimeter) + ($x * $this->perimeter) - (120 * $this->historicalAppointmentAverageDuration - 720);
        };

        $c = $a;
        for ($i = 0; $i < $maxIterations; $i++) {
            $c = ($a + $b) / 2;
            if ($functionValue($c) == 0 || ($b - $a) / 2 < $tolerance) {
                break;
            }
            if ($functionValue($c) * $functionValue($a) > 0) {
                $a = $c;
            } else {
                $b = $c;
            }
        }

        return round($c, 2);
    }

    private function isInclementWeather(): bool
    {
        if ($this->weatherInfo === null) {
            return false;
        }

        return $this->weatherInfo->getWeatherCondition()->isInclement();
    }

    private function getLinearFootPerSecond(float $linearFootPerSecond): float
    {
        return $this->isInclementWeather() ? ($linearFootPerSecond / 2) : $linearFootPerSecond;
    }

    private function calculatePerimeter(): float
    {
        $landSqFt = $this->propertyDetails->getLandSqFt();
        $buildingSqFt = $this->propertyDetails->getBuildingSqFt();

        return 4 * (sqrt($landSqFt) + sqrt($buildingSqFt));
    }
}
