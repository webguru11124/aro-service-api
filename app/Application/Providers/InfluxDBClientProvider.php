<?php

declare(strict_types=1);

namespace App\Application\Providers;

use InfluxDB2\Client as InfluxDB2Client;
use InfluxDB2\Model\WritePrecision;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class InfluxDBClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(InfluxDB2Client::class, function (Application $app) {
            return new InfluxDB2Client([
                'url' => config('influxdb.connection.host'),
                'token' => config('influxdb.connection.token'),
                'bucket' => config('influxdb.connection.bucket'),
                'org' => config('influxdb.connection.organization'),
                'precision' => WritePrecision::S,
                'debug' => false,
                'tags' => [
                    'environment' => config('app.env'),
                ],
                'verifySSL' => false,
            ]);
        });
    }

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            InfluxDB2Client::class,
        ];
    }
}
