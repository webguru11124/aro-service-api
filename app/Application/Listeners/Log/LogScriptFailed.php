<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\FailedEvent;
use Illuminate\Support\Facades\Log;

class LogScriptFailed
{
    /**
     * @param FailedEvent $event
     *
     * @return void
     */
    public function handle(FailedEvent $event): void
    {
        Log::error((string) $event->getException());
    }
}
