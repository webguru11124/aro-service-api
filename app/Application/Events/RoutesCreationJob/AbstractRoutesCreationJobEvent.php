<?php

declare(strict_types=1);

namespace App\Application\Events\RoutesCreationJob;

use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractRoutesCreationJobEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Office $office,
        public readonly CarbonInterface $date,
        public readonly Job|null $job,
        public readonly \Throwable|null $exception = null
    ) {
    }
}
