<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\SlackNotification;

use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use Illuminate\Support\Facades\Http;
use Throwable;

class SlackNotificationClient
{
    public function __construct(
        private string $webhookUrl
    ) {
    }

    /**
     * @param array<string, string> $payload
     *
     * @return void
     * @throws NotificationServiceClientException
     */
    public function sendPost(array $payload): void
    {
        try {
            Http::post($this->webhookUrl, $payload);
        } catch (Throwable $exception) {
            throw NotificationServiceClientException::instance($exception->getMessage(), $payload);
        }
    }
}
