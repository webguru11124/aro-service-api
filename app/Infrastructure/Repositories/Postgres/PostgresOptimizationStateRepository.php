<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Factories\OptimizationStateArrayFactory;
use App\Domain\RouteOptimization\Services\OptimizationStateStatisticsService;
use App\Infrastructure\Formatters\OptimizationStateFormatter;
use App\Infrastructure\Formatters\RouteArrayFormatter;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresOptimizationStateRepository extends AbstractPostgresRepository implements OptimizationStateRepository
{
    public function __construct(
        private OptimizationStateFormatter $optimizationStateFormatter,
        private OptimizationStateStatisticsService $stateStatisticsService,
        private RouteArrayFormatter $routeArrayFormatter,
        private OptimizationStateArrayFactory $optimizationStateFactory,
    ) {
    }

    protected function getTableName(): string
    {
        return PostgresDBInfo::OPTIMIZATION_STATE_TABLE;
    }

    /**
     * Persist optimization state
     *
     * @param OptimizationState $state
     *
     * @return void
     */
    public function save(OptimizationState $state): void
    {
        $formattedState = $this->optimizationStateFormatter->format($state);
        $formattedStats = $this->stateStatisticsService->getStats($state)->toArray();

        $this->getQueryBuilder()
            ->updateOrInsert(
                ['id' => $state->getId()],
                [
                    'previous_state_id' => $state->getPreviousStateId(),
                    'status' => $state->getStatus()->value,
                    'as_of_date' => $state->getDate()->toDateString(),
                    'office' => json_encode($formattedState['office']),
                    'state' => json_encode($formattedState['state']),
                    'stats' => json_encode($formattedStats),
                    'rules' => !empty($formattedState['rules']) ? json_encode($formattedState['rules']) : null,
                    'metrics' => !empty($formattedState['metrics']) ? json_encode($formattedState['metrics']) : null,
                    'weather_forecast' => !empty($formattedState['weather']) ? json_encode($formattedState['weather']) : null,
                ],
            );

        $this->saveRouteDetails($state->getRoutes(), $state->getId());
        $this->saveRouteGeometry($state->getRoutes(), $state->getId());
    }

    /**
     * Get next ID for the optimization state
     *
     * @return int
     */
    public function getNextId(): int
    {
        // Not concerned with SQL injection here as these are developer defined values in the code
        $result = DB::select(sprintf(
            "SELECT NEXTVAL(pg_get_serial_sequence('%s', 'id')) as next_id;",
            $this->getTableName()
        ));

        return $result[0]->next_id;
    }

    private function saveRouteGeometry(Collection $routes, int $stateId): void
    {
        /** @var Collection<Route> $routes */
        foreach ($routes as $route) {
            $geometry = $route->getGeometry();

            if (!$geometry) {
                continue;
            }

            DB::table(PostgresDBInfo::ROUTE_GEOMETRY_TABLE)
                ->updateOrInsert(
                    [
                        'route_id' => $route->getId(),
                        'optimization_state_id' => $stateId,
                    ],
                    [
                        'geometry' => $geometry,
                    ],
                );
        }
    }

    private function saveRouteDetails(Collection $routes, int $stateId): void
    {
        foreach ($routes as $route) {
            $formattedRoute = $this->routeArrayFormatter->format($route);

            DB::table(PostgresDBInfo::ROUTE_DETAILS_TABLE)
                ->updateOrInsert(
                    [
                        'route_id' => $route->getId(),
                        'optimization_state_id' => $stateId,
                    ],
                    [
                        'schedule' => json_encode($formattedRoute['schedule']),
                        'details' => json_encode($formattedRoute['details']),
                        'service_pro' => json_encode($formattedRoute['service_pro']),
                        'metrics' => json_encode($formattedRoute['metrics']),
                        'stats' => json_encode($formattedRoute['stats']),
                    ],
                );
        }
    }

    /**
     * Finds optimization states by ID
     *
     * @param int $stateId
     *
     * @return OptimizationState
     */
    public function findById(int $stateId): OptimizationState
    {
        $state = DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE . ' as os')
            ->select(
                'os.id',
                'os.created_at',
                'os.previous_state_id',
                'os.office',
                'os.state',
                'os.stats',
                'os.metrics',
                'os.weather_forecast',
                'os.rules',
                'os.status',
            )
            ->where('os.id', '=', $stateId)
            ->first();

        $routes = $this->getStateRoutes($stateId);
        $state->routes = $routes->get($state->id);
        $this->decodeStateFields($state);

        return $this->optimizationStateFactory->make((array) $state);
    }

    /**
     * @param int $id
     *
     * @return Collection
     */
    private function getStateRoutes(int $id): Collection
    {
        return DB::table(PostgresDBInfo::ROUTE_DETAILS_TABLE)
            ->select(
                'optimization_state_id',
                'route_id as id',
                'details',
                'schedule',
                'service_pro',
                'metrics',
                'stats',
            )
            ->where('optimization_state_id', '=', $id)
            ->orderBy('route_id')
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
    }

    private function decodeStateFields(\stdClass $state): void
    {
        $state->state = json_decode($state->state, true);
        $state->stats = !empty($state->stats) ? json_decode($state->stats, true) : null;
        $state->metrics = !empty($state->metrics) ? json_decode($state->metrics, true) : null;
        $state->office = !empty($state->office) ? json_decode($state->office, true) : null;
        $state->weather_forecast = !empty($state->weather_forecast) ? json_decode($state->weather_forecast, true) : null;
        $state->rules = !empty($state->rules) ? json_decode($state->rules, true) : null;
        $state->status = OptimizationStatus::tryFrom($state->status);
    }
}
