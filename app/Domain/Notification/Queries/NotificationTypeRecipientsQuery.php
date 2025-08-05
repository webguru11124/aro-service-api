<?php

declare(strict_types=1);

namespace App\Domain\Notification\Queries;

use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use Illuminate\Support\Collection;

interface NotificationTypeRecipientsQuery
{
    /**
     * Returns list of recipients for a given notification type
     *
     * @param NotificationTypeEnum $type
     *
     * @return Collection<Recipient>
     */
    public function get(NotificationTypeEnum $type): Collection;
}
