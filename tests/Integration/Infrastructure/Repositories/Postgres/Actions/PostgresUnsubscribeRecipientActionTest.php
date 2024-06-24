<?php

declare(strict_types=1);

namespace Integration\Infrastructure\Repositories\Postgres\Actions;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Infrastructure\Repositories\Postgres\Actions\PostgresUnsubscribeRecipientAction;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresUnsubscribeRecipientActionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_executes_action(): void
    {
        $notificationTypeId = DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)
            ->insertGetId(['type' => $this->faker->word]);
        $recipientId = DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)
            ->insertGetId([
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'phone' => $this->faker->phoneNumber,
            ]);
        $channel = NotificationChannel::EMAIL->value;

        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE)
            ->insert([
                'type_id' => $notificationTypeId,
                'notification_recipient_id' => $recipientId,
                'notification_channel' => $channel,
            ]);

        $action = new PostgresUnsubscribeRecipientAction();
        $action->execute($recipientId, $notificationTypeId, $channel);

        $this->assertDatabaseMissing(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE, [
            'type_id' => $notificationTypeId,
            'notification_recipient_id' => $recipientId,
            'notification_channel' => $channel,
        ]);
    }
}
