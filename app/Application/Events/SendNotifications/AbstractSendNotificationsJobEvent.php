<?php

declare(strict_types=1);

namespace App\Application\Events\SendNotifications;

use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractSendNotificationsJobEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly CarbonInterface $date,
        public readonly Job|null $job,
        public readonly \Throwable|null $exception = null
    ) {
    }
}
