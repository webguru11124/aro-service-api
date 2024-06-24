<?php

declare(strict_types=1);

namespace App\Application\Events\Vroom;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VroomRequestFailed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private CarbonInterface $time;

    public function __construct(
        public readonly string $requestId
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
