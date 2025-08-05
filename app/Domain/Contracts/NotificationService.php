<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Infrastructure\Services\Notification\Params\NotificationParams;

interface NotificationService
{
    /**
     * Send a notification to a list of recipients.
     *
     * @param NotificationParams $notificationParams
     *
     * @return void
     */
    public function send(NotificationParams $notificationParams): void;
}
