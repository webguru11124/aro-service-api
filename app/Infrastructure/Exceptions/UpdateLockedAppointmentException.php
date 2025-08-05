<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

class UpdateLockedAppointmentException extends \Exception
{
    /**
     * @return self
     */
    public static function instance(): self
    {
        return new self(__('messages.routes_optimization.cant_update_appointment'));
    }
}
