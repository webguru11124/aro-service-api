<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\RoutesCreationJob\RoutesCreationJobEnded;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobFailed;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobStarted;
use App\Application\Listeners\Log\LogRouteCreationJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class LogRouteCreationJobTest extends TestCase
{
    private LogRouteCreationJob $listener;
    private const JOB_TITLE = 'RoutesCreationJob';

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogRouteCreationJob();
    }

    /**
     * @test
     *
     * @dataProvider eventClassProvider
     */
    public function it_listens_events(string $eventClass): void
    {
        Event::fake();

        Event::assertListening($eventClass, LogRouteCreationJob::class);
    }

    public static function eventClassProvider(): array
    {
        return [
            'RoutesCreationJobStarted' => [RoutesCreationJobStarted::class],
            'RoutesCreationJobEnded' => [RoutesCreationJobEnded::class],
            'RoutesCreationJobFailed' => [RoutesCreationJobFailed::class],
        ];
    }

    /**
     * @test
     *
     * @dataProvider eventProvider
     */
    public function it_logs_event($event, $logMethod, $expectedLogMessage): void
    {
        Log::shouldReceive($logMethod)
            ->once()
            ->withArgs(function ($logMessage) use ($expectedLogMessage) {
                return $logMessage === $expectedLogMessage;
            });

        $this->listener->handle($event);
    }

    public static function eventProvider(): array
    {
        $office = OfficeFactory::make();
        $date = Carbon::tomorrow();
        $jobMock = Mockery::mock('Illuminate\Contracts\Queue\Job');
        $jobMock->shouldReceive('getJobId')->andReturn('12345');
        $exception = new \Exception('Test Exception');

        return [
            'RoutesCreationJobStarted' => [
                'event' => new RoutesCreationJobStarted($office, $date, $jobMock),
                'logMethod' => 'info',
                'expectedLogMessage' => self::JOB_TITLE . ' - START',
            ],
            'RoutesCreationJobEnded' => [
                'event' => new RoutesCreationJobEnded($office, $date, $jobMock),
                'logMethod' => 'info',
                'expectedLogMessage' => self::JOB_TITLE . ' - END',
            ],
            'RoutesCreationJobFailed' => [
                'event' => new RoutesCreationJobFailed($office, $date, $jobMock, $exception),
                'logMethod' => 'error',
                'expectedLogMessage' => self::JOB_TITLE . ' - ERROR',
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
    }
}
