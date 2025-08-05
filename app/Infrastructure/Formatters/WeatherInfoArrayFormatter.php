<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;

class WeatherInfoArrayFormatter implements WeatherInfoFormatter
{
    /**
     * @param WeatherInfo $weatherInfo
     *
     * @return array<string, mixed>
     */
    public function format(WeatherInfo $weatherInfo): array
    {
        return [
            'condition' => $weatherInfo->getWeatherCondition()->getMain(),
            'is_inclement' => $weatherInfo->getWeatherCondition()->isInclement(),
            'wind' => $weatherInfo->getWind()->direction . ' ' . $weatherInfo->getWind()->speed,
            'temperature' => $weatherInfo->getTemperature()->temp,
            'pressure' => $weatherInfo->getPressure(),
            'humidity' => $weatherInfo->getHumidity(),
        ];
    }
}
