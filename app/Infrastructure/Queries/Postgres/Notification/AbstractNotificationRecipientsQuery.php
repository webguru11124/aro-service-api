<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Postgres\Notification;

use App\Domain\Notification\Entities\NotificationType;
use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Entities\Subscription;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class AbstractNotificationRecipientsQuery
{
    /**
     * Provides a base query builder for fetching recipients and their notification types.
     *
     * @return Builder The query builder.
     */
    protected function getQueryBuilder(): Builder
    {
        return DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE . ' as recipients')
            ->leftJoin(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE . ' as nrt', 'recipients.id', '=', 'nrt.notification_recipient_id')
            ->leftJoin(PostgresDBInfo::NOTIFICATION_TYPES_TABLE . ' as nt', 'nrt.type_id', '=', 'nt.id')
            ->select(
                'recipients.id as recipient_id',
                'recipients.name as recipient_name',
                'recipients.phone as recipient_phone',
                'recipients.email as recipient_email',
                'nrt.id',
                'nrt.notification_channel',
                'nt.id as notification_type_id',
                'nt.type as notification_type',
            )
            ->whereNull('recipients.deleted_at')
            ->orderBy('recipients.name');
    }

    /**
     * Maps database query results to a collection of Recipient entities.
     *
     * @param Collection $results Database query results.
     *
     * @return Collection<Recipient> A collection of mapped recipient entities.
     */
    protected function mapToRecipientEntities(Collection $results): Collection
    {
        return $results
            ->groupBy('recipient_id')
            ->map(fn ($recipientSubscriptions) => $this->buildRecipient($recipientSubscriptions))
            ->flatten();
    }

    private function buildRecipient(Collection $recipientSubscriptions): Recipient
    {
        $subscriptions = $recipientSubscriptions->map(
            fn (\stdClass $subscription) => $subscription->id ? $this->buildSubscription($subscription) : null
        )
        ->filter();

        return new Recipient(
            id: $recipientSubscriptions->first()->recipient_id,
            name: $recipientSubscriptions->first()->recipient_name,
            phone: $recipientSubscriptions->first()->recipient_phone,
            email: $recipientSubscriptions->first()->recipient_email,
            subscriptions: $subscriptions,
        );
    }

    private function buildSubscription(\stdClass $subscription): Subscription
    {
        return new Subscription(
            id: $subscription->id,
            notificationType: new NotificationType(
                id: $subscription->notification_type_id,
                name: $subscription->notification_type,
            ),
            notificationChannel: NotificationChannel::tryFrom($subscription->notification_channel),
        );
    }
}
