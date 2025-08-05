<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Postgres\Notification;

use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Queries\AllNotificationRecipientsQuery;
use Illuminate\Support\Collection;

class PostgresAllNotificationRecipientsQuery extends AbstractNotificationRecipientsQuery implements AllNotificationRecipientsQuery
{
    /**
     * Returns list of all recipients
     *
     * @return Collection<Recipient>
     */
    public function get(): Collection
    {
        $results = $this->getQueryBuilder()
            ->get();

        return $this->mapToRecipientEntities($results);
    }
}
