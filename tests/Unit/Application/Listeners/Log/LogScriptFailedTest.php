<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\ScriptFailed;
use App\Application\Listeners\Log\LogScriptFailed;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogScriptFailedTest extends TestCase
{
    private LogScriptFailed $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogScriptFailed();
    }

    /**
     * @test
     */
    public function it_logs_script_failed(): void
    {
        $exception = new \Exception($this->faker->text(16));
        $event = new ScriptFailed($exception);

        Log::expects('error')
            ->withSomeOfArgs((string) $exception);

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
    }
}
