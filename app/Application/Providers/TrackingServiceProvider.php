<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Services\TrackingService;
use App\Infrastructure\Services\WebsocketTracking\WebsocketTrackingService;
use Illuminate\Support\ServiceProvider;

class TrackingServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TrackingService::class => WebsocketTrackingService::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            TrackingService::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->when(WebsocketTrackingService::class)
            ->needs('$config')
            ->giveConfig('tracking-service');
    }
}
