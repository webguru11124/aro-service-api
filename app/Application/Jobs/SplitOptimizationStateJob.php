<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SplitOptimizationStateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int */
    public $timeout = 3600; // 1 hour

    /**
     * @param CarbonInterface $startDate
     * @param CarbonInterface $endDate
     * @param int[] $officeIds
     * @param bool $forceUpdate
     */
    public function __construct(
        public readonly CarbonInterface $startDate,
        public readonly CarbonInterface $endDate,
        public readonly array $officeIds,
        public readonly bool $forceUpdate = false,
    ) {
        $this->onQueue(config('queue.queues.route_optimization'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        GetAllOfficesQuery $allOfficesQuery
    ): void {
        Log::info('Starting SplitOptimizationState command');

        $allOffices = $allOfficesQuery->get();
        $offices = $allOffices->filter(fn (Office $office) => empty($this->officeIds) || in_array($office->getId(), $this->officeIds));

        Log::info('Found offices: ' . $offices->count());
        Log::info('Period to process: ' . $this->startDate->toDateString() . ' - ' . $this->endDate->toDateString());

        foreach ($offices as $office) {
            $this->processStatesInOffice($office);
        }

        Log::info('Finished.');
    }

    private function processStatesInOffice(Office $office): void
    {
        Log::info('Processing office ' . $office->getName() . ' (' . $office->getId() . ')');

        $statesTotal = 0;
        $statesProcessed = 0;
        $endDate = $this->endDate->clone();
        $date = $this->startDate->clone();

        do {
            $states = $this->getOptimizationStatesForDateAndOffice($date, $office);
            $statesTotal += $states->count();

            foreach($states as $state) {
                $statesProcessed += $this->processState($state) ? 1 : 0;
            }

            $date->addDay();
        } while ($date->lte($endDate));

        Log::info($statesTotal . ' - states total');
        Log::info($statesProcessed . ' - states processed');
    }

    private function processState(\stdClass $state): bool
    {
        if ($this->isRouteDetailsForState((int) $state->id) && !$this->forceUpdate) {
            return false;
        }

        $optimizationState = json_decode($state->state, true);
        $routes = $optimizationState['routes'] ?? [];

        foreach ($routes as $route) {
            try {
                $details = [
                    'capacity' => $route['capacity'] ?? 0,
                    'start_at' => $route['start_at'] ?? $optimizationState['optimization_window_start'],
                    'end_at' => $route['end_at'] ?? $optimizationState['optimization_window_end'],
                    'start_location' => $route['start_location'],
                    'end_location' => $route['end_location'],
                    'optimization_score' => $route['route_optimization_score'] ?? 0,
                ];

                $servicePro = $route['assigned_service_pro'];
                $servicePro['working_hours'] = $route['service_pro_working_hours'] ?? [];

                $routeStats = $this->getRouteStats($state->id, $route['id']);

                DB::table(PostgresDBInfo::ROUTE_DETAILS_TABLE)
                    ->updateOrInsert(
                        [
                            'route_id' => $route['id'],
                            'optimization_state_id' => $state->id,
                        ],
                        [
                            'schedule' => json_encode($route['schedule']),
                            'details' => json_encode($details),
                            'service_pro' => json_encode($servicePro),
                            'metrics' => !empty($route['metric_details']) ? json_encode($route['metric_details']) : null,
                            'stats' => $routeStats->stats ?? null,
                        ],
                    );
            } catch (\Throwable $e) {
                $office = json_decode($state->office);
                Log::warning('Unable to process OptimizationState', [
                    'optimization_state_id' => $state->id,
                    'date' => $state->as_of_date,
                    'office_id' => $office->office_id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return true;
    }

    private function getOptimizationStatesForDateAndOffice(CarbonInterface $date, Office $office): Collection
    {
        return DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE)
            ->where('office->office_id', $office->getId())
            ->where('as_of_date', $date->toDateString())
            ->get();
    }

    private function isRouteDetailsForState(int $id): bool
    {
        return DB::table(PostgresDBInfo::ROUTE_DETAILS_TABLE)
            ->where('optimization_state_id', $id)
            ->exists();
    }

    private function getRouteStats(int $stateId, int $routeId): \stdClass|null
    {
        return DB::table(PostgresDBInfo::ROUTE_STATS_TABLE)
            ->select('stats')
            ->where('optimization_state_id', $stateId)
            ->where('route_id', $routeId)
            ->first();
    }
}
