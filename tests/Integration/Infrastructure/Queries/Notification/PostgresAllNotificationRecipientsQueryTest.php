<?php

declare(strict_types=1);

namespace Integration\Infrastructure\Queries\Notification;

use App\Domain\Notification\Entities\Recipient;
use App\Infrastructure\Queries\Postgres\Notification\PostgresAllNotificationRecipientsQuery;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresAllNotificationRecipientsQueryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_fetches_all_recipients_correctly(): void
    {
        $recipientId = DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)
            ->insertGetId([
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'email' => $this->faker->email,
            ]);
        $notificationTypeId = DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)
            ->insertGetId([
                'type' => $this->faker->word,
            ]);
        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE)
            ->insert([
                'notification_recipient_id' => $recipientId,
                'type_id' => $notificationTypeId,
            ]);

        $deletedRecipientId = DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)
            ->insertGetId([
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'email' => $this->faker->email,
                'deleted_at' => now(),
            ]);

        $query = new PostgresAllNotificationRecipientsQuery();
        $recipients = $query->get();

        $this->assertGreaterThan(0, $recipients->count());

        /** @var Recipient $recipient */
        $recipient = $recipients->first(fn ($recipient) => $recipient->getId() === $recipientId);
        $this->assertNotNull($recipient);

        $recipient = $recipients->first(fn ($recipient) => $recipient->getId() === $deletedRecipientId);
        $this->assertNull($recipient);
    }
}
