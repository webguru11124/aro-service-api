<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Postgres\Notification;

use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use App\Domain\Notification\Queries\NotificationTypeRecipientsQuery;
use Illuminate\Support\Collection;

class PostgresNotificationTypeRecipientsQuery extends AbstractNotificationRecipientsQuery implements NotificationTypeRecipientsQuery
{
    /**
     * Returns list of recipients for a given notification type
     *
     * @param NotificationTypeEnum $type
     *
     * @return Collection<Recipient>
     */
    public function get(NotificationTypeEnum $type): Collection
    {
        $results = $this->getQueryBuilder()
            ->where('nt.type', '=', $type->value)
            ->get();

        return $this->mapToRecipientEntities($results);
    }
}
