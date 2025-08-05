<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Weather;

use App\Infrastructure\Services\Weather\Exceptions\WeatherClientConfigurationException;
use App\Infrastructure\Services\Weather\Exceptions\WeatherClientException;
use App\Infrastructure\Services\Weather\OpenWeatherMapClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenWeatherMapClientTest extends TestCase
{
    private OpenWeatherMapClient $client;

    private const RETRIES = 3;
    private const MILLISECONDS_TO_WAIT_BETWEEN_RETRIES = 500;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new OpenWeatherMapClient('test_key', 'test_url');
    }

    /** @test */
    public function it_returns_weather_correctly(): void
    {
        $query = ['lat' => 51.5, 'lon' => 0];

        Http::fake([
            '*' => Http::response(['data' => 'test']),
        ]);

        $response = $this->client->get($query);
        $this->assertEquals(['data' => 'test'], $response);
    }

    /** @test */
    public function it_throws_exception_on_failed_request(): void
    {
        $query = ['lat' => 51.5, 'lon' => 0];

        Http::shouldReceive('retry')
            ->with(self::RETRIES, self::MILLISECONDS_TO_WAIT_BETWEEN_RETRIES)
            ->andThrowExceptions([new \Exception('test')]);

        $this->expectException(WeatherClientException::class);
        $this->client->get($query);
    }

    /** @test */
    public function it_throws_exception_on_empty_api_key(): void
    {
        Config::set('open_weather_map.api_key', '');

        $this->expectException(WeatherClientConfigurationException::class);
        $client = new OpenWeatherMapClient('', 'test_url');
        $client->get(['lat' => 1.1, 'lon' => 2.2]);
    }

    /** @test */
    public function it_throws_exception_on_empty_api_url(): void
    {
        Config::set('open_weather_map.api_url', '');

        $this->expectException(WeatherClientConfigurationException::class);
        $client = new OpenWeatherMapClient('test_key', '');
        $client->get(['lat' => 1.1, 'lon' => 2.2]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->client);
    }
}
