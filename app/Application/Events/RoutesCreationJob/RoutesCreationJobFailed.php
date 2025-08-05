<?php

declare(strict_types=1);

namespace App\Application\Events\RoutesCreationJob;

use App\Application\Events\FailedEvent;

class RoutesCreationJobFailed extends AbstractRoutesCreationJobEvent implements FailedEvent
{
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
