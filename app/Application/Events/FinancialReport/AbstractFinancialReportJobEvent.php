<?php

declare(strict_types=1);

namespace App\Application\Events\FinancialReport;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractFinancialReportJobEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $year,
        public readonly string $month,
        public readonly Job|null $job,
        public readonly \Throwable|null $exception = null
    ) {
    }
}
