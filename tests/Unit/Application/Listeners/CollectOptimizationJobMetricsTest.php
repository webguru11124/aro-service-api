<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners;

use App\Application\Events\OptimizationJob\OptimizationJobFinished;
use App\Application\Listeners\CollectOptimizationJobMetrics;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\WriteApi;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

/**
 * @coversDefaultClass CollectOptimizationJobMetrics
 */
class CollectOptimizationJobMetricsTest extends TestCase
{
    private OptimizationJobFinished $event;
    private CollectOptimizationJobMetrics $listener;

    private MockInterface|InfluxClient $mockInfluxClient;
    private MockInterface|WriteApi $mockWriteApi;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        $this->setupMockInfluxClient();
        $this->setupEvent();
        $this->setupListener();
    }

    private function setupMockInfluxClient(): void
    {
        $this->mockWriteApi = Mockery::mock(WriteApi::class);
        $this->mockInfluxClient = Mockery::mock(InfluxClient::class);
        $this->mockInfluxClient
            ->shouldReceive('createWriteApi')
            ->andReturn($this->mockWriteApi);
        $this->instance(InfluxClient::class, $this->mockInfluxClient);
    }

    private function setupEvent(): void
    {
        $this->event = new OptimizationJobFinished(
            OfficeFactory::make(),
            Carbon::now(),
            true,
            10
        );
    }

    private function setupListener(): void
    {
        $this->listener = new CollectOptimizationJobMetrics(
            $this->mockInfluxClient,
        );
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_creates_and_stores_the_metric_in_influxdb(): void
    {
        Log::shouldReceive('info')->twice();
        $this->mockWriteApi
            ->shouldReceive('write')
            ->once();

        $this->listener->handle($this->event);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_logs_an_error_when_an_exception_is_thrown(): void
    {
        Log::shouldReceive('error')->once();

        $this->listener->failed($this->event, new Exception('Some Error'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
        unset($this->event);
        unset($this->mockInfluxClient);
        unset($this->mockWriteApi);
    }
}
