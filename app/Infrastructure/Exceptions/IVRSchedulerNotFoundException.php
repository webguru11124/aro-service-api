<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Exception;

class IVRSchedulerNotFoundException extends Exception
{
    /**
     * @return self
     */
    public static function instance(): self
    {
        return new self(__('messages.not_found.ivr_scheduler'));
    }
}
