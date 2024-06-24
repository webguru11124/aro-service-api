<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Domain\Notification\Enums\NotificationTypeEnum;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationRecipientTypeSeeder extends Seeder
{
    private const EMAIL_TO_TYPE_NAME = [
        'john@example.com' => NotificationTypeEnum::OPTIMIZATION_FAILED->value,
        'jane@example.com' => NotificationTypeEnum::SCORE_OPTIMIZATION->value,
        'jack@example.com' => NotificationTypeEnum::OPTIMIZATION_FAILED->value,
        'donald@example.com' => NotificationTypeEnum::OPTIMIZATION_SKIPPED->value,
    ];

    public function run(): void
    {
        $links = [];

        foreach (self::EMAIL_TO_TYPE_NAME as $email => $typeName) {
            // Fetch the recipient ID based on email
            $recipientId = DB::table('field_operations.notification_recipients')
                ->where('email', $email)
                ->value('id'); // Use value() when you expect a single value

            // Fetch the type ID based on type name
            $typeId = DB::table('field_operations.notification_types')
                ->where('type', $typeName)
                ->value('id'); // Use value() for a single value

            if ($recipientId && $typeId) {
                $links[] = [
                    'notification_recipient_id' => $recipientId,
                    'type_id' => $typeId,
                ];
            }
        }

        if (!empty($links)) {
            DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE)->insert($links);
        }
    }
}
