<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\Weather;

use App\Domain\RouteOptimization\ValueObjects\Weather\Wind;
use App\Domain\RouteOptimization\ValueObjects\Weather\Temperature;
use App\Domain\RouteOptimization\ValueObjects\Weather\WeatherInfoIdentity;

readonly class WeatherInfo
{
    public function __construct(
        private WeatherInfoIdentity $id,
        private Temperature $temperature,
        private WeatherCondition $weatherCondition,
        private Wind $wind,
        private int|null $pressure,
        private int|null $humidity,
    ) {
    }

    /**
     * @return WeatherInfoIdentity
     */
    public function getId(): WeatherInfoIdentity
    {
        return $this->id;
    }

    /**
     * @return Temperature
     */
    public function getTemperature(): Temperature
    {
        return $this->temperature;
    }

    /**
     * @return WeatherCondition
     */
    public function getWeatherCondition(): WeatherCondition
    {
        return $this->weatherCondition;
    }

    /**
     * @return Wind
     */
    public function getWind(): Wind
    {
        return $this->wind;
    }

    /**
     * @return int|null
     */
    public function getPressure(): int|null
    {
        return $this->pressure;
    }

    /**
     * @return int|null
     */
    public function getHumidity(): int|null
    {
        return $this->humidity;
    }
}
