<?php

declare(strict_types=1);

namespace App\Application\Events;

interface FailedEvent
{
    public function getException(): \Throwable;
}
