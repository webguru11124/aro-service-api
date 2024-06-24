<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather\Translators;

use Carbon\CarbonInterface;
use App\Domain\RouteOptimization\ValueObjects\Weather\Wind;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\RouteOptimization\ValueObjects\Weather\Temperature;
use App\Domain\RouteOptimization\Entities\Weather\WeatherCondition;
use App\Infrastructure\Services\Weather\Helpers\WindDirectionHelper;
use App\Domain\RouteOptimization\ValueObjects\Weather\WeatherInfoIdentity;

class WeatherInfoTranslator
{
    /**
     * Translate to WeatherInfo based on provided array
     *
     * @param array<string, mixed> $data
     *
     * @return WeatherInfo
     */
    public function toDomain(array $data, CarbonInterface $date, int $officeId): WeatherInfo
    {
        return new WeatherInfo(
            id: WeatherInfoIdentity::create(
                officeId: $officeId,
                date: $date,
            ),
            temperature: new Temperature(
                temp: $data['main']['temp'] ?? null,
                min: $data['main']['temp_min'] ?? null,
                max: $data['main']['temp_max'] ?? null,
            ),
            weatherCondition: new WeatherCondition(
                id: $data['weather'][0]['id'] ?? null,
                main: $data['weather'][0]['main'] ?? null,
                description: $data['weather'][0]['description'] ?? null,
                iconCode: $data['weather'][0]['icon'] ?? null,
            ),
            wind: new Wind(
                speed: $data['wind']['speed'] ?? null,
                direction: isset($data['wind']['deg']) ? WindDirectionHelper::getTextDirection($data['wind']['deg']) : null,
            ),
            pressure: $data['main']['pressure'] ?? null,
            humidity: $data['main']['humidity'] ?? null,
        );
    }
}
