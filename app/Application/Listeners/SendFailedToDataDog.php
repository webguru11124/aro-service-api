<?php

declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Events\FailedEvent;
use App\Infrastructure\Instrumentation\Datadog\Instrument;

class SendFailedToDataDog
{
    public function handle(FailedEvent $event): void
    {
        Instrument::error($event->getException());
    }
}
