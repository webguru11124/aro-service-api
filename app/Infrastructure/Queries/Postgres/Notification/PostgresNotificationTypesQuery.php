<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Postgres\Notification;

use App\Domain\Notification\Entities\NotificationType;
use App\Domain\Notification\Queries\NotificationTypesQuery;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresNotificationTypesQuery implements NotificationTypesQuery
{
    /**
     * Returns notification types
     *
     * @return Collection<NotificationType>
     */
    public function get(): Collection
    {
        $results = DB::table(PostgresDBInfo::NOTIFICATION_TYPES_TABLE)
            ->orderBy('id')
            ->whereNull('deleted_at')
            ->get();

        return $results->map(function ($result) {
            return new NotificationType(
                id: (int) $result->id,
                name: $result->type,
            );
        });
    }
}
