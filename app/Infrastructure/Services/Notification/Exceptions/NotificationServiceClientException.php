<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Exceptions;

use Exception;

class NotificationServiceClientException extends Exception
{
    /**
     * @param string $error
     * @param array<string, mixed> $payload
     *
     * @return self
     */
    public static function instance(string $error, array $payload): self
    {
        return new self(__('messages.notification.service_client_error', [
            'error' => $error,
            'payload' => json_encode($payload, JSON_PRETTY_PRINT),
        ]));
    }
}
