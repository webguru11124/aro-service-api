<?php

declare(strict_types=1);

namespace App\Application\Providers;

use Google\Cloud\Optimization\V1\Client\FleetRoutingClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class GoogleFleetRoutingClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(FleetRoutingClient::class, function (Application $app) {
            $credsFile = base_path('google-sa.json');
            $creds = file_exists($credsFile)
                ? json_decode(file_get_contents($credsFile), true)
                : config('googleapis.auth');

            return new FleetRoutingClient([
                'credentials' => $creds,
                'transport' => config('googleapis.grpc_enabled') == 1 ? 'grpc' : 'rest',
            ]);
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            FleetRoutingClient::class,
        ];
    }
}
