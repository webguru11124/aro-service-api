<?php

declare(strict_types=1);

namespace App\Application\Events\Vroom;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\SerializesModels;

class VroomResponseReceived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private CarbonInterface $time;

    public function __construct(
        public readonly string $requestId,
        public readonly CarbonInterface $date,
        public readonly int $officeId,
        public readonly Response $response
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
