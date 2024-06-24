<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsEnded;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsFailed;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsStarted;
use App\Application\Listeners\Log\LogCollectOptimizationStateMetrics;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;

class LogCollectOptimizationStateMetricsTest extends TestCase
{
    private LogCollectOptimizationStateMetrics $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogCollectOptimizationStateMetrics();
    }

    /**
     * @test
     */
    public function it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(
            CollectOptimizationStateMetricsStarted::class,
            LogCollectOptimizationStateMetrics::class
        );

        Event::assertListening(
            CollectOptimizationStateMetricsEnded::class,
            LogCollectOptimizationStateMetrics::class
        );

        Event::assertListening(
            CollectOptimizationStateMetricsFailed::class,
            LogCollectOptimizationStateMetrics::class
        );
    }

    /**
     * @test
     */
    public function it_logs_start(): void
    {
        $optimizationState = OptimizationStateFactory::make();
        $event = new CollectOptimizationStateMetricsStarted($optimizationState);

        Log::expects('info')
            ->withSomeOfArgs('STARTED - Collect Optimization State Metrics');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_end(): void
    {
        $optimizationState = OptimizationStateFactory::make();
        $event = new CollectOptimizationStateMetricsEnded($optimizationState);

        Log::expects('info')
            ->withSomeOfArgs('COMPLETE - Collect Optimization State Metrics');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_failed(): void
    {
        $optimizationState = OptimizationStateFactory::make();
        $exception = new \Exception();
        $event = new CollectOptimizationStateMetricsFailed($optimizationState, $exception);

        Log::expects('error')
            ->withSomeOfArgs('FAILED - Collect Optimization State Metrics');

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
    }
}
