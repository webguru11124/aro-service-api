<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Events;

use App\Application\Events\ScriptFailed;
use Tests\TestCase;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ScriptFailedTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_expected_exception(): void
    {
        $exception = $this->createMock(\Throwable::class);

        $scriptFailed = new ScriptFailed($exception);

        $this->assertInstanceOf(ScriptFailed::class, $scriptFailed);

        $this->assertSame($exception, $scriptFailed->getException());
    }

    /**
     * @test
     */
    public function it_returns_current_time(): void
    {
        $scriptFailed = new ScriptFailed($this->createMock(\Throwable::class));

        $currentTime = Carbon::now();
        $eventTime = $scriptFailed->getTime();

        $this->assertInstanceOf(CarbonInterface::class, $eventTime);
        $this->assertGreaterThanOrEqual($currentTime->timestamp - 1, $eventTime->timestamp);
        $this->assertLessThanOrEqual($currentTime->timestamp + 1, $eventTime->timestamp);
    }
}
