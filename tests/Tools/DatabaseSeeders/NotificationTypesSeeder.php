<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Domain\Notification\Enums\NotificationTypeEnum;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTypesSeeder extends Seeder
{
    private const NOTIFICATION_TYPES = [
        ['type' => NotificationTypeEnum::OPTIMIZATION_FAILED->value],
        ['type' => NotificationTypeEnum::SCORE_OPTIMIZATION->value],
        ['type' => NotificationTypeEnum::OPTIMIZATION_SKIPPED->value],
    ];

    public function run(): void
    {
        DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)->insert(self::NOTIFICATION_TYPES);
    }
}
