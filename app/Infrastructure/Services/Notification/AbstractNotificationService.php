<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification;

abstract class AbstractNotificationService
{
    protected NotificationServiceClient $client;

    public function __construct(NotificationServiceClient $client)
    {
        $this->client = $client;
    }
}
