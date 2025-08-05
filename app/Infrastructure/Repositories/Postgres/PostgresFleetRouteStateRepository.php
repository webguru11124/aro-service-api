<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use stdClass;
use App\Domain\Tracking\ValueObjects\OptimizationStateMetrics;
use App\Domain\Contracts\Repositories\FleetRouteStateRepository;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\Factories\FleetRouteFactory;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresFleetRouteStateRepository implements FleetRouteStateRepository
{
    public function __construct(
        private FleetRouteFactory $fleetRouteFactory,
    ) {
    }

    /**
     * Returns fleet routes for specified office ID on date
     *
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return FleetRouteState|null
     */
    public function findByOfficeIdAndDate(int $officeId, CarbonInterface $date): FleetRouteState|null
    {
        $optimizationState = $this->getLatestOptimizationStateForOfficeAndDate($officeId, $date->toDateString());

        if ($optimizationState === null) {
            return null;
        }

        $metrics = !empty($optimizationState->metrics)
            ? $this->createOptimizationMetrics(json_decode($optimizationState->metrics, true))
            : null;

        $routes = $this->getRoutesForOptimizationState($optimizationState->id);

        $route = $routes->first();
        $updatedAt = !empty($route['updated_at'])
            ? Carbon::parse($route['updated_at'], 'UTC')->setTimezone($date->getTimezone())
            : null;

        $fleetRouteState = new FleetRouteState($optimizationState->id, $officeId, $date, $updatedAt, $metrics);

        foreach ($routes as $route) {
            $fleetRoute = $this->fleetRouteFactory->create(
                $route,
                $date->timezone
            );

            if (!$fleetRoute) {
                continue;
            }

            $fleetRouteState->addFleetRoute($fleetRoute);
        }

        return $fleetRouteState;
    }

    /**
     * @param array<string, mixed> $optimizationStateMetrics
     *
     * @return OptimizationStateMetrics
     */
    private function createOptimizationMetrics(array $optimizationStateMetrics): OptimizationStateMetrics
    {
        return new OptimizationStateMetrics(
            totalDriveTime: $optimizationStateMetrics['total_drive_time'] ?? null,
            totalDriveMiles: $optimizationStateMetrics['total_drive_miles'] ?? null,
            optimizationScore: $optimizationStateMetrics['optimization_score'] ?? null,
            totalWorkingHours: $optimizationStateMetrics['total_working_hours'] ?? null,
            totalWeightedServices: $optimizationStateMetrics['total_weighted_services'] ?? null,
            averageTimeBetweenServices: $optimizationStateMetrics['average_time_between_services'] ?? null,
            averageMilesBetweenServices: $optimizationStateMetrics['average_miles_between_services'] ?? null,
            averageWeightedServicesPerHour: $optimizationStateMetrics['average_weighted_services_per_hour'] ?? null,
        );
    }

    private function getLatestOptimizationStateForOfficeAndDate(int $officeId, string $date): \stdClass|null
    {
        return DB::connection('pgsql')
            ->table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE)
            ->select('id', 'state', 'metrics')
            ->where('office->office_id', $officeId)
            ->where('as_of_date', $date)
            ->where('status', '=', OptimizationStatus::POST)
            ->orderBy('updated_at', 'desc')
            ->first();
    }

    private function getRoutesForOptimizationState(int $optimizationStateId): Collection
    {
        return DB::connection('pgsql')
            ->table(PostgresDBInfo::ROUTE_DETAILS_TABLE . ' as rd')
            ->select(
                'rd.route_id',
                'rd.details as details',
                'rd.schedule as schedule',
                'rd.service_pro as service_pro',
                'rd.metrics as metrics',
                'rd.stats as route_stats',
                'rg.geometry as geometry',
                'srd.stats as actual_stats',
                'srd.updated_at'
            )
            ->leftJoin(PostgresDBInfo::SERVICED_ROUTE_DETAILS_TABLE . ' as srd', 'rd.route_id', '=', 'srd.route_id')
            ->leftJoin(PostgresDBInfo::ROUTE_GEOMETRY_TABLE . ' as rg', function ($join) {
                $join->on('rd.route_id', '=', 'rg.route_id')
                    ->on('rd.optimization_state_id', '=', 'rg.optimization_state_id');
            })
            ->where('rd.optimization_state_id', $optimizationStateId)
            ->get()
            ->mapWithKeys(fn (stdClass $row) => [
                $row->route_id => [
                    'route_id' => $row->route_id,
                    'details' => json_decode($row->details, true),
                    'schedule' => json_decode($row->schedule, true),
                    'service_pro' => json_decode($row->service_pro, true),
                    'metrics' => json_decode($row->metrics, true),
                    'route_stats' => json_decode($row->route_stats, true),
                    'actual_stats' => !empty($row->actual_stats) ? json_decode($row->actual_stats, true) : [],
                    'geometry' => $row->geometry,
                    'updated_at' => $row->updated_at,
                ],
            ]);
    }
}
