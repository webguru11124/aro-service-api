<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationJob;

use App\Application\Events\FailedEvent;

class OptimizationJobFailed extends AbstractOptimizationJobEvent implements FailedEvent
{
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
