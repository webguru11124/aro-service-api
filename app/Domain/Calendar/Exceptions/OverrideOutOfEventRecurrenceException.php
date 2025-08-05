<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Exceptions;

use Exception;
use Aptive\Component\Http\HttpStatus;

class OverrideOutOfEventRecurrenceException extends Exception
{
    /**
     * @return self
     */
    public static function instance(): self
    {
        return new self(__('messages.calendar.override_out_of_event_recurrence'), HttpStatus::BAD_REQUEST);
    }
}
