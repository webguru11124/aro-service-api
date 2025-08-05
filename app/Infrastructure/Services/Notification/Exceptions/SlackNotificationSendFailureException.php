<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Exceptions;

use Exception;

class SlackNotificationSendFailureException extends Exception
{
    /**
     * @param string $errorMessage
     *
     * @return self
     */
    public static function instance(string $errorMessage): self
    {
        $message = __('messages.notification.slack_send_failure', [
            'error' => $errorMessage,
        ]);

        return new self($message);
    }
}
