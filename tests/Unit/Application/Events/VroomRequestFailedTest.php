<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Events;

use App\Application\Events\Vroom\VroomRequestFailed;
use Tests\TestCase;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class VroomRequestFailedTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_current_time(): void
    {
        $requestId = 'requestId';
        $failedRequest = new VroomRequestFailed($requestId);

        $currentTime = Carbon::now();
        $eventTime = $failedRequest->getTime();

        $this->assertInstanceOf(CarbonInterface::class, $eventTime);
        $this->assertGreaterThanOrEqual($currentTime->timestamp - 1, $eventTime->timestamp);
        $this->assertLessThanOrEqual($currentTime->timestamp + 1, $eventTime->timestamp);
    }
}
