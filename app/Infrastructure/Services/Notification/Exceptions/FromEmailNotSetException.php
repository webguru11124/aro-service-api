<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Exceptions;

use Exception;

class FromEmailNotSetException extends Exception
{
    /**
     * @return self
     */
    public static function instance(): self
    {
        return new self(__('messages.notification.from_email_not_set_in_config'));
    }
}
