<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Jobs\RefreshAvailableSpotsCacheJob;
use App\Infrastructure\Services\PestRoutes\Actions\RefreshAvailableSpotsCacheAction;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

class RefreshAvailableSpotsCacheJobTest extends TestCase
{
    private const TTL = 60;

    private CarbonInterface $startDate;
    private RefreshAvailableSpotsCacheJob $job;

    private RefreshAvailableSpotsCacheAction|MockInterface $mockAction;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->startDate = Carbon::today();
        $this->mockAction = Mockery::mock(RefreshAvailableSpotsCacheAction::class);

        $this->job = new RefreshAvailableSpotsCacheJob(
            [TestValue::OFFICE_ID],
            $this->startDate,
            $this->startDate,
            self::TTL
        );
    }

    /**
     * @test
     */
    public function it_executes_action_to_refresh_cache(): void
    {
        $this->mockAction
            ->shouldReceive('execute')
            ->with(TestValue::OFFICE_ID, $this->startDate, $this->startDate, self::TTL)
            ->once();

        Log::shouldReceive('info')->twice();

        $this->executeJob();
    }

    /**
     * @test
     */
    public function it_logs_error_when_an_exception_thrown(): void
    {
        $this->mockAction
            ->shouldReceive('execute')
            ->once()
            ->andThrow(new Exception('Failed to refresh cache'));

        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->twice();

        $this->executeJob();
    }

    private function executeJob(): void
    {
        $this->job->handle($this->mockAction);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->job);
        unset($this->startDate);
        unset($this->mockAction);
    }
}
