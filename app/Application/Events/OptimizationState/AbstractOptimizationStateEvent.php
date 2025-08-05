<?php

declare(strict_types=1);

namespace App\Application\Events\OptimizationState;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractOptimizationStateEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private CarbonInterface $time;

    /**
     * @param OptimizationState $optimizationState
     */
    public function __construct(public readonly OptimizationState $optimizationState)
    {
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
