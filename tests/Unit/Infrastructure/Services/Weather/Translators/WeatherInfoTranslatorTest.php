<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Weather\Translators;

use Carbon\Carbon;
use Tests\TestCase;
use App\Infrastructure\Services\Weather\Translators\WeatherInfoTranslator;

class WeatherInfoTranslatorTest extends TestCase
{
    private WeatherInfoTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = new WeatherInfoTranslator();
    }

    /**
     * @test
     */
    public function it_should_translate_to_domain(): void
    {
        $officeId = 1;
        $date = Carbon::createFromTimestamp(1663587600);
        $data = [
            'dt' => 1663587600,
            'sunrise' => 1663562340,
            'sunset' => 1663605700,
            'main' => [
                'temp' => 62.06,
                'feels_like' => 62.38,
                'temp_min' => 59.32,
                'temp_max' => 65.08,
                'pressure' => 1019,
                'humidity' => 94,
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
                'speed' => 10,
                'deg' => 20,
                'gust' => 12.01,
            ],
            'clouds' => [
                'all' => 0,
            ],
            'rain' => [
                '1h' => 7.5,
            ],
            'pop' => 0,
        ];

        $weatherInfo = $this->translator->toDomain($data, $date, $officeId);

        $this->assertEquals($date->toDateString(), $weatherInfo->getId()->getDate()->toDateString());
        $this->assertEquals($officeId, $weatherInfo->getId()->getOfficeId());
        $this->assertEquals(94, $weatherInfo->getHumidity());
        $this->assertEquals(1019, $weatherInfo->getPressure());
        $this->assertEquals(62.06, $weatherInfo->getTemperature()->temp);
        $this->assertEquals(59.32, $weatherInfo->getTemperature()->min);
        $this->assertEquals(65.08, $weatherInfo->getTemperature()->max);
        $this->assertEquals(800, $weatherInfo->getWeatherCondition()->getId());
        $this->assertEquals('Clear', $weatherInfo->getWeatherCondition()->getMain());
        $this->assertEquals('clear sky', $weatherInfo->getWeatherCondition()->getDescription());
        $this->assertEquals('01d', $weatherInfo->getWeatherCondition()->getIconCode());
        $this->assertEquals(10, $weatherInfo->getWind()->speed);
        $this->assertEquals('N', $weatherInfo->getWind()->direction);
        $this->assertFalse($weatherInfo->getWeatherCondition()->isInclement());
    }

    /**
     * @test
     */
    public function it_should_translate_to_domain_with_inclement_weather(): void
    {
        $officeId = 1;
        $date = Carbon::createFromTimestamp(1663587600);
        $data = [
            'dt' => 1663587600,
            'sunrise' => 1663562340,
            'sunset' => 1663605700,
            'main' => [
                'day' => 62.06,
                'feel_like' => 62.38,
                'temp_min' => 59.32,
                'temp_max' => 65.08,
                'pressure' => 1019,
                'humidity' => 94,
            ],
            'weather' => [
                [
                    'id' => 200,
                    'main' => 'Thunderstorm',
                    'description' => 'thunderstorm with light rain',
                    'icon' => '11d',
                ],
            ],
            'wind' => [
                'speed' => 10,
                'deg' => 20,
                'gust' => 12.01,
            ],
            'clouds' => [
                'all' => 0,
            ],
            'rain' => [
                '1h' => 7.5,
            ],
            'pop' => 0,
        ];

        $weatherInfo = $this->translator->toDomain($data, $date, $officeId);

        $this->assertEquals('Thunderstorm', $weatherInfo->getWeatherCondition()->getMain());
        $this->assertEquals('thunderstorm with light rain', $weatherInfo->getWeatherCondition()->getDescription());
        $this->assertTrue($weatherInfo->getWeatherCondition()->isInclement());
    }
}
