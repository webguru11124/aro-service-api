<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresSchedulingDataRepository
{
    /**
     * Finds scheduling data by IDs
     *
     * @param int[] $stateIds
     *
     * @return Collection
     */
    public function searchByIds(array $stateIds): Collection
    {
        return DB::table(PostgresDBInfo::SCHEDULING_STATES_TABLE)
            ->select(
                'id',
                'office_id',
                'as_of_date',
                'created_at',
                'pending_services',
                'stats',
            )
            ->whereIn('id', $stateIds)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Searches for execution of scheduling states by date
     *
     * @param CarbonInterface $date
     *
     * @return Collection
     */
    public function searchExecutionsByDate(CarbonInterface $date): Collection
    {
        return DB::table(PostgresDBInfo::SCHEDULING_STATES_TABLE)
            ->select(
                'id',
                'as_of_date',
                'office_id',
                'stats',
                'created_at',
            )
            ->where('created_at', '>=', $date->clone()->startOfDay()->toDateTimeString())
            ->where('created_at', '<=', $date->clone()->endOfDay()->toDateTimeString())
            ->orderBy('as_of_date')
            ->orderBy('created_at')
            ->get()
            ->map(function ($item) {
                $item->stats = json_decode($item->stats ?? '{}', true);

                return $item;
            });
    }

    /**
     * Finds scheduled routes data by state IDs
     *
     * @param int $stateId
     *
     * @return Collection
     */
    public function searchScheduledRoutesByStateId(int $stateId): Collection
    {
        return DB::table(PostgresDBInfo::SCHEDULED_ROUTE_DETAILS)
            ->select(
                'route_id',
                'details',
                'pending_services',
                'appointments',
                'service_pro',
            )
            ->where('scheduling_state_id', '=', $stateId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Finds scheduling states by office ID for given date
     *
     * @param int $officeId
     * @param CarbonInterface $schedulingDate
     *
     * @return Collection
     */
    public function findStatesByOfficeIdAndDate(int $officeId, CarbonInterface $schedulingDate): Collection
    {
        return DB::table(PostgresDBInfo::SCHEDULING_STATES_TABLE)
            ->select(
                'id',
                'stats',
                'created_at',
            )
            ->where('as_of_date', '=', $schedulingDate->toDateString())
            ->where('office_id', '=', $officeId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->stats = json_decode($item->stats ?? '', true);

                return $item;
            });
    }
}
