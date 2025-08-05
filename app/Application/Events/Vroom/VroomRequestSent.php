<?php

declare(strict_types=1);

namespace App\Application\Events\Vroom;

use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VroomRequestSent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private CarbonInterface $time;

    public function __construct(
        public readonly string $requestId,
        public readonly string $url,
        public readonly CarbonInterface $date,
        public readonly int $officeId,
        public readonly VroomInputData $inputData
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
