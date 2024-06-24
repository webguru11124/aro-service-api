<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Exceptions;

use Exception;

class EventNotFoundException extends Exception
{
    /**
     * @param int $id
     *
     * @return self
     */
    public static function instance(int $id): self
    {
        return new self(__('messages.calendar.event_not_found', [
            'event_id' => $id,
        ]));
    }
}
