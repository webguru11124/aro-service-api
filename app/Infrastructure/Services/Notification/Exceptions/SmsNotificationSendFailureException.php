<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Exceptions;

use Exception;

class SmsNotificationSendFailureException extends Exception
{
    /**
     * @param string[] $failedRecipients
     *
     * @return self
     */
    public static function instance(array $failedRecipients): self
    {
        $message = __('messages.notification.sms_send_failure', [
            'recipients' => implode(',', $failedRecipients),
        ]);

        return new self($message);
    }
}
