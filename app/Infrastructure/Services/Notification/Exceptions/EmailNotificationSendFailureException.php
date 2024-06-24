<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Exceptions;

use Exception;

class EmailNotificationSendFailureException extends Exception
{
    /**
     * @param string[] $failedRecipients
     *
     * @return self
     */
    public static function instance(array $failedRecipients): self
    {
        return new self(__('messages.notification.email_send_failure', [
            'recipients' => implode(',', $failedRecipients),
        ]));
    }
}
