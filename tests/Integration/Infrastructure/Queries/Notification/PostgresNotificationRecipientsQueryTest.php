<?php

declare(strict_types=1);

namespace Integration\Infrastructure\Queries\Notification;

use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use App\Infrastructure\Queries\Postgres\Notification\PostgresNotificationTypeRecipientsQuery;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresNotificationRecipientsQueryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresNotificationTypeRecipientsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query = new PostgresNotificationTypeRecipientsQuery();
    }

    /**
     * @test
     */
    public function it_fetches_recipients_for_given_subscription_by_notification_type(): void
    {
        $recipientId = DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)
            ->insertGetId([
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'email' => $this->faker->email,
            ]);
        $notificationTypeId = DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)
            ->insertGetId([
                'type' => NotificationTypeEnum::OPTIMIZATION_FAILED->value,
            ]);
        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE)
            ->insert([
                'notification_recipient_id' => $recipientId,
                'type_id' => $notificationTypeId,
            ]);

        $recipients = $this->query->get(NotificationTypeEnum::OPTIMIZATION_FAILED);

        $this->assertGreaterThan(0, $recipients->count());

        /** @var Recipient $recipient */
        $recipient = $recipients->first(fn ($recipient) => $recipient->getId() === $recipientId);
        $this->assertNotNull($recipient);
    }

    /**
     * @test
     */
    public function it_returns_empty_result_when_no_recipients_found_for_given_notification_type(): void
    {
        $recipientId = DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)
            ->insertGetId([
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'email' => $this->faker->email,
            ]);
        $notificationTypeId = DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)
            ->insertGetId([
                'type' => NotificationTypeEnum::OPTIMIZATION_FAILED->value,
            ]);
        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENT_TYPE_TABLE)
            ->insert([
                'notification_recipient_id' => $recipientId,
                'type_id' => $notificationTypeId,
            ]);

        $recipients = $this->query->get(NotificationTypeEnum::OPTIMIZATION_SKIPPED);

        $this->assertTrue($recipients->isEmpty());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->query);
    }
}
