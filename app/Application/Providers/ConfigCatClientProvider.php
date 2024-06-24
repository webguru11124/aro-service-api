<?php

declare(strict_types=1);

namespace App\Application\Providers;

use ConfigCat\ConfigCatClient;
use ConfigCat\ClientOptions;
use ConfigCat\Log\LogLevel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class ConfigCatClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ConfigCatClient::class, function (Application $app) {
            return new ConfigCatClient(
                config('configcat.auth.sdk_key'),
                [
                    ClientOptions::LOG_LEVEL => LogLevel::INFO,
                    ClientOptions::LOGGER => $app->make(LoggerInterface::class),

                    // TODO: Add caching of feature flag configs once we have redis connection to this service
                    // \ConfigCat\ClientOptions::CACHE =>
                    //     new \ConfigCat\Cache\LaravelCache(\Illuminate\Support\Facades\Cache::store()),
                    // \ConfigCat\ClientOptions::CACHE_REFRESH_INTERVAL => 300 // 300 Seconds or 5 Mins is the cache TTL
                ]
            );
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ConfigCatClient::class,
        ];
    }
}
