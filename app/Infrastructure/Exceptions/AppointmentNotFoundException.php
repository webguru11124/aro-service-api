<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Exception;

class AppointmentNotFoundException extends Exception
{
    /**
     * @param int $appointmentId
     *
     * @return self
     */
    public static function instance(int $appointmentId): self
    {
        return new self(__('messages.not_found.appointment', [
            'id' => $appointmentId,
        ]));
    }
}
