<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\Weather;

use Carbon\Carbon;
use Tests\TestCase;
use App\Domain\RouteOptimization\ValueObjects\Weather\Wind;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\RouteOptimization\ValueObjects\Weather\Temperature;
use App\Domain\RouteOptimization\Entities\Weather\WeatherCondition;
use App\Domain\RouteOptimization\ValueObjects\Weather\WeatherInfoIdentity;

class WeatherInfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $weatherIdentity = new WeatherInfoIdentity(
            officeId: 1,
            date: Carbon::now(),
        );

        $temperature = new Temperature(
            temp: 10.0,
            min: 5.0,
            max: 15.0,
        );

        $weatherInfo = new WeatherInfo(
            id: $weatherIdentity,
            temperature: $temperature,
            weatherCondition: new WeatherCondition(
                id: 200,
                main: 'Thunderstorm',
                description: 'thunderstorm with light rain',
                iconCode: '11d',
            ),
            wind: new Wind(
                speed: 3.6,
                direction: 'NE',
            ),
            pressure: 1000,
            humidity: 50,
        );

        $this->assertEquals($weatherIdentity, $weatherInfo->getId());
        $this->assertEquals($temperature, $weatherInfo->getTemperature());
        $this->assertEquals(200, $weatherInfo->getWeatherCondition()->getId());
        $this->assertEquals('Thunderstorm', $weatherInfo->getWeatherCondition()->getMain());
        $this->assertEquals('thunderstorm with light rain', $weatherInfo->getWeatherCondition()->getDescription());
        $this->assertEquals('11d', $weatherInfo->getWeatherCondition()->getIconCode());
        $this->assertEquals(3.6, $weatherInfo->getWind()->speed);
        $this->assertEquals('NE', $weatherInfo->getWind()->direction);
    }
}
