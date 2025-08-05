<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Infrastructure\Services\Motive\Client\Client as MotiveClient;
use App\Infrastructure\Services\Motive\Client\HttpClient\LaravelHttpClient;
use App\Infrastructure\Services\Motive\ConfigCredentialsRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class MotiveClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(MotiveClient::class, function () {
            $httpClient = new LaravelHttpClient(
                new ConfigCredentialsRepository()
            );

            $cache = Cache::repository(Cache::getStore());

            return new MotiveClient($httpClient, $cache);
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            MotiveClient::class,
        ];
    }
}
