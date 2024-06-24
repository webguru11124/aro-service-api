<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Weather;

use Mockery;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Weather\OpenWeatherMapResponseData;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Weather\OpenWeatherMapClient;
use App\Infrastructure\Services\Weather\OpenWeatherMapService;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Infrastructure\Services\Weather\Translators\WeatherInfoTranslator;
use App\Infrastructure\Services\Weather\Exceptions\WeatherServiceException;

class OpenWeatherMapServiceTest extends TestCase
{
    private OpenWeatherMapClient $mockedClient;
    private WeatherInfoTranslator $weatherTranslator;
    private OpenWeatherMapService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockedClient = Mockery::mock(OpenWeatherMapClient::class);
        $this->weatherTranslator = new WeatherInfoTranslator();
        $this->service = new OpenWeatherMapService($this->mockedClient, $this->weatherTranslator);
    }

    /** @test */
    public function it_returns_weather_on_valid_coordinates(): void
    {
        $longitude = 0;
        $latitude = 51.5;
        $location = new Coordinate($latitude, $longitude);
        $office = OfficeFactory::make();
        $date = Carbon::createFromTimestamp(1663587600);
        Carbon::setTestNow($date);

        $openWeatherMapResponseData = OpenWeatherMapResponseData::getWeatherInfoData();

        $this->mockedClient->shouldReceive('get')
            ->with(['lat' => $latitude, 'lon' => $longitude, 'cnt' => 40])
            ->once()
            ->andReturn($openWeatherMapResponseData);

        $weatherData = $this->service->getCurrentWeatherByCoordinates($office, $date, $location);

        $this->assertInstanceOf(WeatherInfo::class, $weatherData);

        $noonForecast = $this->findNoonForecast($openWeatherMapResponseData['list'], $date);

        $this->assertEquals($noonForecast['main']['humidity'], $weatherData->getHumidity());
        $this->assertEquals($noonForecast['main']['pressure'], $weatherData->getPressure());
        $this->assertEquals($noonForecast['main']['temp'], $weatherData->getTemperature()->temp);
        $this->assertEquals($noonForecast['main']['temp_min'], $weatherData->getTemperature()->min);
        $this->assertEquals($date->toDateString(), $weatherData->getId()->getDate()->toDateString());
        $this->assertEquals($office->getId(), $weatherData->getId()->getOfficeId());
        $this->assertEquals($noonForecast['weather'][0]['id'], $weatherData->getWeatherCondition()->getId());
        $this->assertEquals($noonForecast['weather'][0]['main'], $weatherData->getWeatherCondition()->getMain());
        $this->assertEquals($noonForecast['weather'][0]['description'], $weatherData->getWeatherCondition()->getDescription());
        $this->assertEquals($noonForecast['weather'][0]['icon'], $weatherData->getWeatherCondition()->getIconCode());
        $this->assertEquals($noonForecast['wind']['speed'], $weatherData->getWind()->speed);
        $this->assertEquals('N', $weatherData->getWind()->direction);
    }

    private function findNoonForecast(array $forecasts, Carbon $date): array
    {
        foreach ($forecasts as $forecast) {
            $forecastTime = Carbon::createFromTimestamp($forecast['dt']);
            if ($forecastTime->isSameDay($date) && $forecastTime->hour >= 12) {
                return $forecast;
            }
        }

        return $forecasts[0];
    }

    /** @test */
    public function it_throws_exception_on_past_date(): void
    {
        $longitude = 0;
        $latitude = 51.5;
        $location = new Coordinate($latitude, $longitude);
        $office = OfficeFactory::make();
        $date = Carbon::createFromTimestamp(1663587600);
        $futureDate = $date->copy()->addDays();
        Carbon::setTestNow($futureDate);

        $this->expectException(WeatherServiceException::class);
        $this->service->getCurrentWeatherByCoordinates($office, $date, $location);
    }

    /** @test */
    public function it_throws_exception_on_date_over_than_forecast(): void
    {
        $longitude = 0;
        $latitude = 51.5;
        $location = new Coordinate($latitude, $longitude);
        $office = OfficeFactory::make();
        $date = Carbon::createFromTimestamp(1663587600);
        $futureDate = $date->copy()->addDays(6);
        Carbon::setTestNow($futureDate);

        $this->expectException(WeatherServiceException::class);
        $this->service->getCurrentWeatherByCoordinates($office, $date, $location);
    }

    /** @test */
    public function it_throws_exception_on_empty_response(): void
    {
        $longitude = 0;
        $latitude = 51.5;
        $location = new Coordinate($latitude, $longitude);
        $office = OfficeFactory::make();
        $date = Carbon::createFromTimestamp(1663587600);
        Carbon::setTestNow($date);

        $this->mockedClient->shouldReceive('get')
            ->with(['lat' => $latitude, 'lon' => $longitude, 'cnt' => 40])
            ->once()
            ->andReturn([]);

        $this->expectException(WeatherServiceException::class);
        $this->service->getCurrentWeatherByCoordinates($office, $date, $location);
    }
}
