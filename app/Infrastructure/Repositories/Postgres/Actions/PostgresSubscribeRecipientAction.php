<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres\Actions;

use App\Domain\Notification\Actions\SubscribeRecipientAction;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Facades\DB;

class PostgresSubscribeRecipientAction implements SubscribeRecipientAction
{
    /**
     * Subscribe recipient to a notification channel
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
            ->updateOrInsert([
                'type_id' => $notificationTypeId,
                'notification_recipient_id' => $recipientId,
                'notification_channel' => $channel,
            ]);
    }
}
