<?php

declare(strict_types=1);

namespace Tests\Tools\Weather;

class OpenWeatherMapResponseData
{
    /**
     * Simulates 1 full day of 3-hour interval forecasts.
     *
     * @return array<string, mixed>
     */
    public static function getWeatherInfoData(): array
    {
        $startTimestamp = 1663587600;

        $forecasts = [];
        for ($i = 0; $i < 8; $i++) {
            $timestamp = $startTimestamp + ($i * 3600 * 3);

            $forecasts[] = [
                'dt' => $timestamp,
                'main' => [
                    'temp' => 62.06 + ($i * 0.5),
                    'feels_like' => 62.38 + ($i * 0.5),
                    'temp_min' => 59.32 + ($i * 0.5),
                    'temp_max' => 65.08 + ($i * 0.5),
                    'pressure' => 1019,
                    'humidity' => 94 - ($i * 2),
                ],
                'weather' => [
                    [
                        'id' => 800,
                        'main' => 'Clear',
                        'description' => 'clear sky',
                        'icon' => '01d',
                    ],
                ],
                'wind' => [
                    'speed' => 10 + ($i * 0.1),
                    'deg' => 20,
                    'gust' => 12.01 + ($i * 0.1),
                ],
                'clouds' => [
                    'all' => 0,
                ],
                'pop' => 0,
                'rain' => [
                    '1h' => 7.5,
                ],
            ];
        }

        return [
            'cod' => '200',
            'message' => 0.0,
            'cnt' => 8,
            'list' => $forecasts,
            'city' => [
                'id' => 4206547,
                'name' => 'Loganville',
                'coord' => [
                    'lat' => 33.8384,
                    'lon' => -83.9002,
                ],
                'country' => 'US',
                'population' => 11079,
                'timezone' => -14400,
                'sunrise' => 1663562340,
                'sunset' => 1663605700,
            ],
        ];
    }
}
