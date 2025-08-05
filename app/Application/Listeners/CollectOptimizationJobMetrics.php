<?php

declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Events\OptimizationJob\OptimizationJobFinished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\Point;
use InfluxDB2\WriteApi;
use Throwable;

class CollectOptimizationJobMetrics implements ShouldQueue
{
    private const MEASUREMENT_POINT = 'optimization_run';

    private OptimizationJobFinished $event;
    private WriteApi $writeApi;

    public function __construct(
        private InfluxClient $influxClient,
    ) {
    }

    /**
     * Get the name of the listener's queue.
     */
    public function viaQueue(): string
    {
        return config('queue.queues.collect-metrics');
    }

    /**
     * Handle the event
     *
     * @param OptimizationJobFinished $event
     *
     * @return void
     */
    public function handle(OptimizationJobFinished $event): void
    {
        $this->setEvent($event);
        $this->setupInfluxClientWriteApi();
        $this->collectMetric();
    }

    private function setEvent(OptimizationJobFinished $event): void
    {
        $this->event = $event;
    }

    private function setupInfluxClientWriteApi(): void
    {
        $this->writeApi = $this->influxClient->createWriteApi();
    }

    private function collectMetric(): void
    {
        Log::info('CollectOptimizationJobMetrics - START');

        $point = new Point(self::MEASUREMENT_POINT);
        $point->addField('office_id', $this->event->office->getId())
            ->addTag('office', $this->event->office->getName())
            ->addField('as_of_date', $this->event->date->toDateString())
            ->addField('succeeded', $this->event->succeeded)
            ->addField('process_time', $this->event->processTime)
            ->time($this->event->getTime()->timestamp);

        $this->writeApi->write($point);

        Log::info('CollectOptimizationJobMetrics - END');
    }

    /**
     * Handle a job failure.
     *
     * @param OptimizationJobFinished $event
     * @param Throwable $exception
     *
     * @return void
     */
    public function failed(OptimizationJobFinished $event, Throwable $exception): void
    {
        Log::error('CollectOptimizationJobMetrics - ERROR', [
            'error' => $exception->getMessage(),
        ]);
    }
}
