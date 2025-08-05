<?php

declare(strict_types=1);

namespace Integration\Infrastructure\Queries\Notification;

use App\Domain\Notification\Entities\NotificationType;
use App\Infrastructure\Queries\Postgres\Notification\PostgresNotificationTypesQuery;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresNotificationTypesQueryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_fetches_notification_types_correctly(): void
    {
        DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)->insert([
            ['type' => 'type1', 'deleted_at' => null],
            ['type' => 'type2', 'deleted_at' => null],
            ['type' => 'type3', 'deleted_at' => now()],
        ]);

        $query = new PostgresNotificationTypesQuery();
        $notificationTypes = $query->get();

        $this->assertTrue($notificationTypes->contains(fn (NotificationType $type) => $type->getName() === 'type1'));
        $this->assertTrue($notificationTypes->contains(fn (NotificationType $type) => $type->getName() === 'type2'));
    }
}
