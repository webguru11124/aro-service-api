<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Application\Listeners\Notifications\SendOptimizationFailedNotification;
use App\Application\Listeners\Notifications\SendRoutesExcludedNotification;
use App\Application\Listeners\Notifications\SendOptimizationSkippedNotification;
use App\Application\Listeners\Notifications\SendSchedulingFailedNotification;
use App\Application\Listeners\Notifications\SendSchedulingSkippedNotification;
use App\Domain\Notification\Actions\AddRecipientAction;
use App\Domain\Notification\Actions\SubscribeRecipientAction;
use App\Domain\Notification\Actions\UnsubscribeRecipientAction;
use App\Domain\Notification\Queries\AllNotificationRecipientsQuery;
use App\Domain\Notification\Queries\FailedOptimizationRecipientsQuery;
use App\Domain\Notification\Queries\NotificationTypeRecipientsQuery;
use App\Domain\Notification\Queries\NotificationTypesQuery;
use App\Domain\Notification\Queries\OptimizationScoreRecipientsQuery;
use App\Domain\Notification\Queries\OptimizationSkippedRecipientsQuery;
use App\Infrastructure\Queries\Postgres\Notification\PostgresAllNotificationRecipientsQuery;
use App\Infrastructure\Queries\Postgres\Notification\PostgresNotificationTypeRecipientsQuery;
use App\Infrastructure\Queries\Postgres\Notification\PostgresNotificationTypesQuery;
use App\Infrastructure\Repositories\Postgres\Actions\PostgresAddRecipientAction;
use App\Infrastructure\Repositories\Postgres\Actions\PostgresSubscribeRecipientAction;
use App\Infrastructure\Repositories\Postgres\Actions\PostgresUnsubscribeRecipientAction;
use App\Infrastructure\Services\Notification\Senders\EmailNotificationSender;
use App\Infrastructure\Services\Notification\Senders\NotificationSender;
use App\Infrastructure\Services\Notification\Senders\SmsNotificationSender;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class NotificationsProvider extends ServiceProvider implements DeferrableProvider
{
    private const array NOTIFICATION_SENDERS = [
        SmsNotificationSender::class,
        EmailNotificationSender::class,
    ];

    private const array NOTIFICATION_SUBJECTS = [
        SendOptimizationFailedNotification::class,
        SendOptimizationSkippedNotification::class,
        SendSchedulingFailedNotification::class,
        SendSchedulingSkippedNotification::class,
        SendRoutesExcludedNotification::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        SubscribeRecipientAction::class => PostgresSubscribeRecipientAction::class,
        UnsubscribeRecipientAction::class => PostgresUnsubscribeRecipientAction::class,
        AddRecipientAction::class => PostgresAddRecipientAction::class,

        AllNotificationRecipientsQuery::class => PostgresAllNotificationRecipientsQuery::class,
        NotificationTypeRecipientsQuery::class => PostgresNotificationTypeRecipientsQuery::class,
        NotificationTypesQuery::class => PostgresNotificationTypesQuery::class,
    ];

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            NotificationSender::class,

            SubscribeRecipientAction::class,
            UnsubscribeRecipientAction::class,
            AddRecipientAction::class,

            OptimizationScoreRecipientsQuery::class,
            OptimizationSkippedRecipientsQuery::class,
            FailedOptimizationRecipientsQuery::class,
            AllNotificationRecipientsQuery::class,
            NotificationTypeRecipientsQuery::class,
            NotificationTypesQuery::class,
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerNotificationSenders();
    }

    /**
     * Register notification senders for each notification.
     */
    private function registerNotificationSenders(): void
    {
        $senders = $this->getSenders();

        foreach (self::NOTIFICATION_SUBJECTS as $subject) {
            $this->app->when($subject)
                ->needs(NotificationSender::class)
                ->give(fn ($app) => $senders);
        }
    }

    /**
     * @return array<int, NotificationSender>
     * @throws BindingResolutionException
     */
    private function getSenders(): array
    {
        return array_map(function ($sender) {
            return $this->app->make($sender);
        }, self::NOTIFICATION_SENDERS);
    }
}
