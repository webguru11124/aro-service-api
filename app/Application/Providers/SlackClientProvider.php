<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Infrastructure\Services\Notification\SlackNotification\SlackNotificationClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SlackClientProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(SlackNotificationClient::class, function () {
            return new SlackNotificationClient(
                config('notification-service.slack.aro_notifications_app_webhook_url'),
            );
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            SlackNotificationClient::class,
        ];
    }
}
