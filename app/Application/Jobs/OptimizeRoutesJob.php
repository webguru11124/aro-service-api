<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Application\Events\OptimizationJob\OptimizationJobFailedToUpdateLockedAppointment;
use App\Application\Events\OptimizationJob\OptimizationJobEnded;
use App\Application\Events\OptimizationJob\OptimizationJobFailed;
use App\Application\Events\OptimizationJob\OptimizationJobFinished;
use App\Application\Events\OptimizationJob\OptimizationJobStarted;
use App\Application\Events\OptimizationSkipped;
use App\Application\Events\OptimizationState\OptimizationStateStored;
use App\Domain\Contracts\OptimizationStatePersister;
use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\Contracts\Services\OptimizationPostProcessService;
use App\Domain\Contracts\Services\WeatherService;
use App\Domain\Contracts\OptimizationStateResolver;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Exceptions\InvalidTotalWeightOfMetricsException;
use App\Domain\RouteOptimization\Exceptions\UnknownRouteOptimizationEngineIdentifier;
use App\Domain\RouteOptimization\Services\OptimizationService;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoAppointmentsFoundException;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Exceptions\RoutesHaveNoCapacityException;
use App\Infrastructure\Exceptions\UpdateLockedAppointmentException;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Performs the optimization for a set of routes within an office
 */
class OptimizeRoutesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * When working with AWS SQS and timeout is greater than 30 sec then make sure that Visibility Timeout in AWS SQS is set to the maximum time
     * that it takes application to process and delete a message from the queue.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    private OptimizationStateRepository $optimizationStateRepository;
    private OptimizationService $optimizationService;
    private OptimizationStateResolver $optimizationStateResolver;
    private OptimizationState $sourceOptimizationState;
    private OptimizationPostProcessService $optimizationPostProcessService;
    private WeatherService $weatherService;
    private OptimizationStatePersister $optimizationStatePersister;

    public function __construct(
        public readonly CarbonInterface $date,
        public readonly Office $office,
        public readonly OptimizationParams $optimizationParams,
        public readonly int|null $customTries = null,
    ) {
        $this->onQueue(config('queue.queues.route_optimization'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        OptimizationStateResolver $optimizationStateResolver,
        OptimizationStatePersister $optimizationStatePersister,
        OptimizationService $optimizationService,
        OptimizationPostProcessService $optimizationPostProcessService,
        OptimizationStateRepository $optimizationStateRepository,
        WeatherService $weatherService,
    ): void {
        $this->optimizationStateResolver = $optimizationStateResolver;
        $this->optimizationStatePersister = $optimizationStatePersister;
        $this->optimizationService = $optimizationService;
        $this->optimizationPostProcessService = $optimizationPostProcessService;
        $this->optimizationStateRepository = $optimizationStateRepository;
        $this->weatherService = $weatherService;

        OptimizationJobStarted::dispatch($this->office, $this->date, $this->job);
        $success = true;
        $time = -microtime(true);

        try {
            $this->resolveSourceData();
            $this->doPlanning();
            $this->doOptimization();
        } catch (NoRegularRoutesFoundException|NoServiceProFoundException|NoAppointmentsFoundException|RoutesHaveNoCapacityException $exception
        ) {
            Log::notice($exception->getMessage());
            $success = false;
            OptimizationSkipped::dispatch($this->office, $this->date, $exception);
        } catch (UpdateLockedAppointmentException $exception) {
            Log::notice($exception->getMessage());
            $success = false;
            OptimizationJobFailedToUpdateLockedAppointment::dispatch(
                $this->office,
                $this->date,
                $this->job,
                $exception
            );
        }

        OptimizationJobEnded::dispatch($this->office, $this->date, $this->job);
        $time += microtime(true);
        OptimizationJobFinished::dispatch($this->office, $this->date, $success, $time);
    }

    /**
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     * @throws NoAppointmentsFoundException
     * @throws RoutesHaveNoCapacityException
     */
    private function resolveSourceData(): void
    {
        $this->sourceOptimizationState = $this->optimizationStateResolver->resolve(
            $this->date,
            $this->office,
            $this->optimizationParams
        );
        $this->resolveWeatherInfo();
        $this->storeOptimizationState($this->sourceOptimizationState);
    }

    private function resolveWeatherInfo(): void
    {
        $centralCoordinates = $this->sourceOptimizationState->getAreaCentralPoint();

        if ($centralCoordinates === null) {
            return;
        }

        try {
            $weatherInfo = $this->weatherService->getCurrentWeatherByCoordinates(
                $this->sourceOptimizationState->getOffice(),
                $this->sourceOptimizationState->getDate(),
                $centralCoordinates,
            );
            $this->sourceOptimizationState->setWeatherInfo($weatherInfo);
        } catch (\Throwable $e) {
            Log::warning(__('messages.weather.weather_forecast_is_unavailable', ['error' => $e->getMessage()]));

            return;
        }
    }

    private function doPlanning(): void
    {
        if (!$this->sourceOptimizationState->getOptimizationParams()->buildPlannedOptimization) {
            Log::notice(__('messages.routes_optimization.planning_skipped', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]));

            return;
        }

        $plannedOptimizationState = $this->optimizationService->plan($this->sourceOptimizationState);

        if ($plannedOptimizationState->getStatus() !== OptimizationStatus::PLAN) {
            Log::notice(__('messages.routes_optimization.planning_failed', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]));

            return;
        }

        $this->storeOptimizationState($plannedOptimizationState);
    }

    /**
     * @throws UnknownRouteOptimizationEngineIdentifier
     * @throws InvalidTotalWeightOfMetricsException
     */
    private function doOptimization(): void
    {
        $resultOptimizationState = $this->optimizationService->optimize($this->sourceOptimizationState);
        $this->persistOptimizedData($resultOptimizationState);
        $this->storeOptimizationState($resultOptimizationState);
    }

    private function persistOptimizedData(OptimizationState $optimizationState): void
    {
        if ($optimizationState->getOptimizationParams()->simulationRun) {
            Log::notice(__('messages.routes_optimization.simulation_run', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]));

            return;
        }

        $this->optimizationStatePersister->persist($optimizationState);
        $this->optimizationPostProcessService->execute($this->date, $optimizationState);
    }

    private function storeOptimizationState(OptimizationState $state): void
    {
        $this->optimizationStateRepository->save($state);
        OptimizationStateStored::dispatch($state);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        OptimizationJobFailed::dispatch($this->office, $this->date, $this->job, $exception);
        OptimizationJobFinished::dispatch($this->office, $this->date);
    }

    public function tries(): int
    {
        if (!is_null($this->customTries)) {
            return $this->customTries;
        }

        $jobName = class_basename($this);

        return Config::get('jobs.tries.' . $jobName . '.' . app()->environment());
    }
}
