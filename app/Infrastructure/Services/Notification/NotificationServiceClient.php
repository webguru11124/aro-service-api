<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification;

use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotificationServiceClient
{
    private string $apiUrl;
    private string $apiBearerToken;

    public function __construct()
    {
        $this->apiUrl = Config::get('notification-service.auth.api_url');
        $this->apiBearerToken = Config::get('notification-service.auth.api_bearer_token');
    }

    /**
     * This method sends a POST request to the notification service.
     *
     * @param array<string, mixed> $payload
     *
     * @return void
     * @throws NotificationServiceClientException
     */
    public function sendPost(array $payload): void
    {
        try {
            Http::withToken($this->apiBearerToken)
                ->post($this->apiUrl, $payload);
        } catch (Throwable $exception) {
            throw NotificationServiceClientException::instance($exception->getMessage(), $payload);
        }
    }
}
