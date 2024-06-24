<?php

declare(strict_types=1);

namespace App\Application\Events;

use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OptimizationSkipped
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Office $office,
        public readonly CarbonInterface $date,
        public readonly Exception $exception
    ) {
    }
}
