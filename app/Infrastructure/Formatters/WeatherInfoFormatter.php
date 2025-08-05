<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;

interface WeatherInfoFormatter
{
    /**
     * Formats the weather info in a given way
     *
     * @param WeatherInfo $weatherInfo
     *
     * @return array<string, mixed>
     */
    public function format(WeatherInfo $weatherInfo): array;
}
