<?php

declare(strict_types=1);

namespace App\Application\Events\SendNotifications;

use App\Application\Events\FailedEvent;

class SendNotificationsJobFailed extends AbstractSendNotificationsJobEvent implements FailedEvent
{
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
