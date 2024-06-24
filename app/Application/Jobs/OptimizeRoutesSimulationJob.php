<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Exceptions\InvalidTotalWeightOfMetricsException;
use App\Domain\RouteOptimization\Exceptions\OptimizationStateNotFoundException;
use App\Domain\RouteOptimization\Exceptions\UnknownRouteOptimizationEngineIdentifier;
use App\Domain\RouteOptimization\Services\OptimizationService;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Infrastructure\Helpers\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Performs the optimization for a set of routes within an office
 */
class OptimizeRoutesSimulationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const JOB_NAME = 'OptimizeRoutesSimulationJob';
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 min

    private OptimizationState $sourceOptimizationState;
    private OptimizationState $resultOptimizationState;

    private OptimizationStateRepository $optimizationStateRepository;
    private OptimizationService $optimizationService;

    /**
     * @param int $sourceStateId
     * @param string[] $disabledRules
     */
    public function __construct(
        public readonly int $sourceStateId,
        public readonly array $disabledRules,
    ) {
        $this->onQueue(config('queue.queues.route_optimization'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        OptimizationService $optimizationService,
        OptimizationStateRepository $optimizationStateRepository,
    ): void {
        $this->optimizationService = $optimizationService;
        $this->optimizationStateRepository = $optimizationStateRepository;

        $this->logJobStart();

        try {
            $this->resolveSourceData();
            $this->doOptimization();
            $this->storeOptimizationState();
        } catch (OptimizationStateNotFoundException $exception) {
            Log::notice($exception->getMessage());
        }

        $this->logJobEnd();
    }

    /**
     * @throws OptimizationStateNotFoundException
     */
    private function resolveSourceData(): void
    {
        $this->sourceOptimizationState = $this->optimizationStateRepository->findById($this->sourceStateId);
        $this->sourceOptimizationState->setOptimizationParams(
            new OptimizationParams(
                simulationRun: true,
                disabledRules: $this->disabledRules
            )
        );
    }

    /**
     * @throws UnknownRouteOptimizationEngineIdentifier
     * @throws InvalidTotalWeightOfMetricsException
     */
    private function doOptimization(): void
    {
        $this->resultOptimizationState = $this->optimizationService->optimize($this->sourceOptimizationState);
    }

    private function storeOptimizationState(): void
    {
        $this->optimizationStateRepository->save($this->resultOptimizationState);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error(self::JOB_NAME . ' Processing - ERROR', [
            'job_name' => self::JOB_NAME,
            'job_id' => $this->job?->getJobId(),
            'source_state_id' => $this->sourceStateId,
            'failure_reason' => $exception->getMessage(),
            'failed_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
            'stack_trace' => $exception->getTrace(),
        ]);
    }

    private function logJobStart(): void
    {
        Log::info(self::JOB_NAME . ' Processing - START', [
            'job_name' => self::JOB_NAME,
            'job_id' => $this->job?->getJobId(),
            'source_state_id' => $this->sourceStateId,
            'disabled_rules' => $this->disabledRules,
            'message' => 'Start processing',
            'started_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobEnd(): void
    {
        Log::info(self::JOB_NAME . ' Processing - END', [
            'job_name' => self::JOB_NAME,
            'job_id' => $this->job?->getJobId(),
            'source_state_id' => $this->sourceStateId,
            'message' => 'End processing',
            'end_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }
}
