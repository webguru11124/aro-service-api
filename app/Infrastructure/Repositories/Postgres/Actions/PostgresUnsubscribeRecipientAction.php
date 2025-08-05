<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres\Actions;

use App\Domain\Notification\Actions\UnsubscribeRecipientAction;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Facades\DB;

class PostgresUnsubscribeRecipientAction implements UnsubscribeRecipientAction
{
    /**
     * Unsubscribe recipient from a notification channel
     *
     * @param int $recipientId
     * @param int $notificationTypeId
     * @param string $channel
     *
     * @return void
     */
    public function execute(int $recipientId, int $notificationTypeId, string $channel): void
    {
        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE)
            ->where('type_id', $notificationTypeId)
            ->where('notification_recipient_id', $recipientId)
            ->where('notification_channel', $channel)
            ->delete();
    }
}
