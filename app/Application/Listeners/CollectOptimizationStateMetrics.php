<?php

declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Events\OptimizationState\OptimizationStateUpdated;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsEnded;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsFailed;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsStarted;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\Point;
use InfluxDB2\WriteApi;
use Throwable;

class CollectOptimizationStateMetrics implements ShouldQueue
{
    private const OPTIMIZATION_COMPLETED = 'optimization_completed';
    private const OPTIMIZATION_STARTED = 'optimization_started';

    private OptimizationStateUpdated $event;
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
     * @param OptimizationStateUpdated $event
     *
     * @return void
     */
    public function handle(OptimizationStateUpdated $event): void
    {
        $this->setEvent($event);
        $this->setupInfluxClientWriteApi();

        CollectOptimizationStateMetricsStarted::dispatch($event->optimizationState);
        $this->collectOptimizationStateMetric();
        CollectOptimizationStateMetricsEnded::dispatch($event->optimizationState);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOptimizationStateMetrics(): array
    {
        $optimizationState = $this->event->optimizationState;

        return [
            'optimization_state_id' => $optimizationState->getId(),
            'previous_optimization_state_id' => $optimizationState->getPreviousStateId(),
        ];
    }

    private function setEvent(OptimizationStateUpdated $event): void
    {
        $this->event = $event;
    }

    private function setupInfluxClientWriteApi(): void
    {
        $this->writeApi = $this->influxClient->createWriteApi();
    }

    private function writeMeasurementPoint(Point $point): void
    {
        $point->time($this->event->getTime()->timestamp);
        $this->writeApi->write($point);
    }

    private function collectOptimizationStateMetric(): void
    {
        $point = new Point($this->getMeasurementPointNameByStatus());
        $this->addOfficeFieldAndTag($point);

        foreach ($this->getOptimizationStateMetrics() as $metric => $value) {
            $point->addField($metric, $value);
        }

        $this->writeMeasurementPoint($point);
    }

    private function addOfficeFieldAndTag(Point $point): void
    {
        $point->addField('office_id', $this->event->optimizationState->getOffice()->getId())
            ->addTag('office', $this->event->optimizationState->getOffice()->getName());
    }

    private function getMeasurementPointNameByStatus(): string
    {
        return match ($this->event->optimizationState->getStatus()) {
            OptimizationStatus::PRE, OptimizationStatus::PLAN => self::OPTIMIZATION_STARTED,
            OptimizationStatus::POST, OptimizationStatus::SIMULATION => self::OPTIMIZATION_COMPLETED,
        };
    }

    /**
     * Handle a job failure.
     *
     * @param OptimizationStateUpdated $event
     * @param Throwable $exception
     *
     * @return void
     */
    public function failed(OptimizationStateUpdated $event, Throwable $exception): void
    {
        CollectOptimizationStateMetricsFailed::dispatch($event->optimizationState, $exception);
    }
}
