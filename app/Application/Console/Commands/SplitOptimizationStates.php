<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SplitOptimizationStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:split-optimization-states {--start_date= : start date to process optimization states for} {--end_date= : end date to process optimization states for} {--force_update : forces update of route details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It extracts routes from OptimizationState stored as solid json object and saves them into route_details table';

    private bool $forceUpdate;
    private CarbonInterface $startDate;
    private CarbonInterface $endDate;

    /**
     * Execute the console command.
     */
    public function handle(
        GetAllOfficesQuery $allOfficesQuery
    ): int {
        $this->info('Starting SplitOptimizationState command');

        $this->startDate = Carbon::parse($this->option('start_date'));
        $this->endDate = $this->option('end_date')
            ? Carbon::parse($this->option('end_date'))
            : Carbon::today();
        $this->forceUpdate = $this->option('force_update') ?: false;

        $offices = $allOfficesQuery->get();
        $this->info('Found offices: ' . $offices->count());
        $this->info('Period to process: ' . $this->startDate->toDateString() . ' - ' . Carbon::today()->toDateString());

        foreach ($offices as $office) {
            $this->processStatesInOffice($office);
        }

        $this->info('Finished.');

        return 0;
    }

    private function processStatesInOffice(Office $office): void
    {
        $this->info('Processing office ' . $office->getName() . ' (' . $office->getId() . ')');

        $statesTotal = 0;
        $statesProcessed = 0;
        $today = $this->endDate->clone();
        $date = $this->startDate->clone();

        do {
            $states = $this->getOptimizationStatesForDateAndOffice($date, $office);
            $statesTotal += $states->count();

            foreach($states as $state) {
                $statesProcessed += $this->processState($state) ? 1 : 0;
            }

            $date->addDay();
        } while ($date->lte($today));

        $this->info($statesTotal . ' - states total');
        $this->info($statesProcessed . ' - states processed');
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
