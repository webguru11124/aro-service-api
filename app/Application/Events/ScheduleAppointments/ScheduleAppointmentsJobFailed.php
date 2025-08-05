<?php

declare(strict_types=1);

namespace App\Application\Events\ScheduleAppointments;

use App\Application\Events\FailedEvent;

class ScheduleAppointmentsJobFailed extends AbstractScheduleAppointmentsJobEvent implements FailedEvent
{
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
