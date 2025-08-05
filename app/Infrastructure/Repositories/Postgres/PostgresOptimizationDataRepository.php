<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresOptimizationDataRepository
{
    /**
     * Finds optimization states by IDs
     *
     * @param int[] $stateIds
     *
     * @return Collection
     */
    public function searchByIds(array $stateIds): Collection
    {
        $states = DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE . ' as os')
            ->select(
                'os.id',
                'os.created_at',
                'os.previous_state_id',
                'os.state',
                'os.stats',
                'os.metrics',
                'os.weather_forecast',
                'os.rules',
                'os.status',
            )
            ->whereIn('os.id', $stateIds)
            ->orderBy('os.created_at')
            ->get();

        $routes = $this->getStateRoutes($stateIds);

        foreach ($states as $state) {
            $state->routes = $routes->get($state->id);
            $this->decodeStateFields($state);
        }

        return $states->map(fn ($state) => (array) $state);
    }

    private function decodeStateFields(\stdClass $state): void
    {
        $state->state = json_decode($state->state, true);
        $state->stats = !empty($state->stats) ? json_decode($state->stats, true) : null;
        $state->metrics = !empty($state->metrics) ? json_decode($state->metrics, true) : null;
        $state->weather_forecast = !empty($state->weather_forecast) ? json_decode($state->weather_forecast, true) : null;
        $state->rules = !empty($state->rules) ? json_decode($state->rules, true) : null;
        $state->status = OptimizationStatus::tryFrom($state->status);
    }

    /**
     * @param int[] $stateIds
     *
     * @return Collection<mixed[]>
     */
    public function searchByPreviousStateIds(array $stateIds): Collection
    {
        $states = DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE . ' as os')
            ->select(
                'os.id',
                'os.created_at',
                'os.previous_state_id',
                'os.state',
                'os.stats',
                'os.metrics',
                'os.weather_forecast',
                'os.rules',
                'os.status',
            )
            ->whereIn('os.previous_state_id', $stateIds)
            ->orderBy('os.created_at')
            ->get();

        $foundStateIds = $states->pluck('id')->toArray();
        $routes = $this->getStateRoutes($foundStateIds);

        foreach ($states as $state) {
            $state->routes = $routes->get($state->id);
            $this->decodeStateFields($state);
        }

        return $states->map(fn ($state) => (array) $state);
    }

    /**
     * @param int[] $ids
     *
     * @return Collection
     */
    private function getStateRoutes(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection();
        }

        $results = DB::table(PostgresDBInfo::ROUTE_DETAILS_TABLE . ' as rd')
            ->select(
                'rd.optimization_state_id',
                'rd.route_id as id',
                'rd.details',
                'rd.schedule',
                'rd.service_pro',
                'rd.metrics',
                'rd.stats',
                'rg.geometry',
            )
            ->leftJoin(PostgresDBInfo::ROUTE_GEOMETRY_TABLE . ' as rg', function ($join) {
                $join->on('rd.route_id', '=', 'rg.route_id')
                    ->on('rd.optimization_state_id', '=', 'rg.optimization_state_id');
            })
            ->whereIn('rd.optimization_state_id', $ids)
            ->orderBy('rd.route_id')
            ->get()
            ->map(
                function (\stdClass $row) {
                    $row->details = json_decode($row->details, true);
                    $row->schedule = json_decode($row->schedule, true);
                    $row->service_pro = json_decode($row->service_pro, true);
                    $row->metrics = !empty($row->metrics) ? json_decode($row->metrics, true) : null;
                    $row->stats = !empty($row->stats) ? json_decode($row->stats, true) : null;

                    return (array) $row;
                }
            )
            ->groupBy('optimization_state_id');

        return $results;
    }

    /**
     * Finds optimization states by office ID for given date
     *
     * @param int $officeId
     * @param CarbonInterface $optimizationDate
     *
     * @return Collection
     */
    public function findStatesIdsByOfficeIdAndDate(int $officeId, CarbonInterface $optimizationDate): Collection
    {
        return DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE . ' as pre_os')
            ->select(
                'pre_os.id as id',
                'pre_os.created_at',
            )
            ->where('pre_os.as_of_date', '=', $optimizationDate->toDateString())
            ->where('pre_os.office->office_id', '=', $officeId)
            ->where('pre_os.status', '=', OptimizationStatus::PRE)
            ->orderBy('pre_os.created_at', 'desc')
            ->get();
    }

    /**
     * Searches for PRE and POST optimization states to determine optimization executions
     *
     * @param CarbonInterface $date
     *
     * @return Collection
     */
    public function searchExecutionsByDate(CarbonInterface $date): Collection
    {
        $preStates = DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE . ' as pre_os')
            ->select(
                'pre_os.id as state_id',
                'pre_os.as_of_date as as_of_date',
                'pre_os.office->office_id as office_id',
                'pre_os.created_at as recorded_at',
                'pre_os.state->created_at as created_at',
            )
            ->where('pre_os.created_at', '>=', $date->clone()->startOfDay()->toDateTimeString())
            ->where('pre_os.created_at', '<=', $date->clone()->endOfDay()->toDateTimeString())
            ->where('pre_os.status', '=', OptimizationStatus::PRE)
            ->orderBy('pre_os.as_of_date')
            ->orderBy('pre_os.created_at')
            ->get();

        $prestateIds = $preStates->pluck('state_id')->toArray();

        $postStates = DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE . ' as post_os')
            ->select(
                'post_os.previous_state_id',
                'post_os.metrics as metrics',
                'post_os.stats as stats',
            )
            ->whereIn('post_os.previous_state_id', $prestateIds)
            ->where('post_os.status', '=', OptimizationStatus::POST)
            ->get()
            ->keyBy('previous_state_id');

        return $preStates->map(function ($row) use ($postStates) {
            $result = (array) $row;
            $post = $postStates->get($row->state_id);
            $result['success'] = (int) !empty($post);

            if ($post) {
                $metrics = !empty($post->metrics) ? json_decode($post->metrics, true) : null;
                $result[MetricKey::OPTIMIZATION_SCORE->value] = $metrics[MetricKey::OPTIMIZATION_SCORE->value] ?? 0;
                $result['stats'] = json_decode($post->stats, true);
            }

            return (object) $result;
        });
    }

    /**
     * Finds all offices
     *
     * @return Collection
     */
    public function findOffices(): Collection
    {
        return DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE)
            ->distinct()
            ->select(
                'office->office_id as office_id',
                'office->office as office',
            )
            ->orderBy('office')
            ->get();
    }
}
