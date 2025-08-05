<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\OptimizationJob\OptimizationJobEnded;
use App\Application\Events\OptimizationJob\OptimizationJobFailed;
use App\Application\Events\OptimizationJob\OptimizationJobStarted;
use App\Application\Listeners\Log\LogOptimizationJob;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class LogOptimizationJobTest extends TestCase
{
    private LogOptimizationJob $listener;
    private Office $office;
    private CarbonInterface $date;
    private Job|MockInterface $jobMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogOptimizationJob();

        $this->office = OfficeFactory::make();
        $this->date = Carbon::tomorrow();
        $this->jobMock = \Mockery::mock(Job::class);
        $this->jobMock->shouldReceive('getJobId')->andReturn($this->faker->randomNumber(3));
    }

    /**
     * @test
     */
    public function it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(
            OptimizationJobStarted::class,
            LogOptimizationJob::class
        );

        Event::assertListening(
            OptimizationJobEnded::class,
            LogOptimizationJob::class
        );

        Event::assertListening(
            OptimizationJobFailed::class,
            LogOptimizationJob::class
        );
    }

    /**
     * @test
     */
    public function it_logs_start(): void
    {
        $event = new OptimizationJobStarted($this->office, $this->date, $this->jobMock);

        Log::expects('info')
            ->withSomeOfArgs('OptimizeRoutesJob Processing - START');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_end(): void
    {
        $event = new OptimizationJobEnded($this->office, $this->date, $this->jobMock);

        Log::expects('info')
            ->withSomeOfArgs('OptimizeRoutesJob Processing - END');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_failed(): void
    {
        $exception = new \Exception();
        $event = new OptimizationJobFailed($this->office, $this->date, $this->jobMock, $exception);

        Log::expects('error')
            ->withSomeOfArgs('OptimizeRoutesJob Processing - ERROR');

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
        unset($this->office);
        unset($this->date);
        unset($this->jobMock);
    }
}
