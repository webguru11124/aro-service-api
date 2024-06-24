<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Senders;

interface NotificationSender
{
    /**
     * Send notifications
     *
     * @param NotificationSenderParams $params
     *
     * @return void
     */
    public function send(NotificationSenderParams $params): void;
}
