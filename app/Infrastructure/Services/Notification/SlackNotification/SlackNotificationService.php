<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\SlackNotification;

use App\Infrastructure\Services\Notification\Exceptions\SlackNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Params\NotificationParams;
use App\Infrastructure\Services\Notification\Params\SlackNotificationParams;
use Throwable;

class SlackNotificationService
{
    public function __construct(
        private SlackNotificationClient $client,
    ) {
    }

    /**
     * @param SlackNotificationParams $notificationParams
     *
     * @return void
     * @throws SlackNotificationSendFailureException
     */
    public function send(NotificationParams $notificationParams): void
    {
        try {
            $this->client->sendPost([
                'text' => $notificationParams->body,
            ]);
        } catch (Throwable $exception) {
            throw SlackNotificationSendFailureException::instance($exception->getMessage());
        }
    }
}
