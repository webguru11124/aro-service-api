<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Services\WeatherService;
use App\Infrastructure\Formatters\WeatherInfoArrayFormatter;
use App\Infrastructure\Formatters\WeatherInfoFormatter;
use App\Infrastructure\Services\Weather\OpenWeatherMapClient;
use App\Infrastructure\Services\Weather\OpenWeatherMapService;
use Illuminate\Support\ServiceProvider;

class WeatherServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        WeatherService::class => OpenWeatherMapService::class,
        WeatherInfoFormatter::class => WeatherInfoArrayFormatter::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            WeatherService::class,
            WeatherInfoFormatter::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->singleton(OpenWeatherMapClient::class, function () {
            return new OpenWeatherMapClient(
                config('open-weather-map.api_key', ''),
                config('open-weather-map.api_url', ''),
            );
        });
    }
}
