<?php

declare(strict_types=1);

namespace App\Domain\Notification\Queries;

use App\Domain\Notification\Entities\NotificationType;
use Illuminate\Support\Collection;

interface NotificationTypesQuery
{
    /**
     * Returns notification types
     *
     * @return Collection<NotificationType>
     */
    public function get(): Collection;
}
