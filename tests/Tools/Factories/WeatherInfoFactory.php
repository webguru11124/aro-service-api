<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use Carbon\Carbon;
use App\Domain\RouteOptimization\ValueObjects\Weather\Wind;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\RouteOptimization\ValueObjects\Weather\Temperature;
use App\Domain\RouteOptimization\Entities\Weather\WeatherCondition;
use App\Domain\RouteOptimization\ValueObjects\Weather\WeatherInfoIdentity;

class WeatherInfoFactory extends AbstractFactory
{
    protected function single($overrides = []): WeatherInfo
    {
        $weatherCondition = new WeatherCondition(
            id: 200,
            main: 'Thunderstorm',
            description: 'thunderstorm with light rain',
            iconCode: '11d',
        );

        $wind = new Wind(
            speed: 3.6,
            direction: 'NE',
        );

        $temperature = new Temperature(
            temp: 20.0,
            min: 20.0,
            max: 20.0,
        );

        $weatherInfoIdentity = new WeatherInfoIdentity(
            officeId: 1,
            date: Carbon::now(),
        );

        return new WeatherInfo(
            id: $overrides['id'] ?? $weatherInfoIdentity,
            temperature: $overrides['temperature'] ?? $temperature,
            weatherCondition: $overrides['weatherCondition'] ?? $weatherCondition,
            wind: $overrides['wind'] ?? $wind,
            pressure: $overrides['pressure'] ?? $this->faker->numberBetween(0, 100),
            humidity: $overrides['humidity'] ?? $this->faker->numberBetween(0, 100),
        );
    }
}
