<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationJob;

use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OptimizationJobFinished
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    private CarbonInterface $time;

    public function __construct(
        public readonly Office $office,
        public readonly CarbonInterface $date,
        public readonly bool $succeeded = false,
        public readonly float $processTime = 0,
    ) {
        $this->time = Carbon::now();
    }

    /**
     * @return CarbonInterface
     */
    public function getTime(): CarbonInterface
    {
        return $this->time;
    }
}
