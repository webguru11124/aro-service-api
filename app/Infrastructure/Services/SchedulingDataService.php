<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Infrastructure\Repositories\Postgres\PostgresSchedulingDataRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SchedulingDataService
{
    private const TIME_ZONE = 'America/Denver';

    public function __construct(
        private PostgresSchedulingDataRepository $repository,
    ) {
    }

    /**
     * Returns scheduling states overview for given office and date
     *
     * @param int $officeId
     * @param CarbonInterface $optimizationDate
     * @param CarbonInterface|null $executionDate
     *
     * @return Collection
     */
    public function getSchedulingOverview(
        int $officeId,
        CarbonInterface $optimizationDate,
        CarbonInterface|null $executionDate = null
    ): Collection {
        $allStates = $this->repository->findStatesByOfficeIdAndDate($officeId, $optimizationDate);

        if ($allStates->isEmpty()) {
            return new Collection();
        }

        $executionDate = is_null($executionDate)
            ? Carbon::parse($allStates->first()->created_at)->setTimezone(self::TIME_ZONE)
            : Carbon::parse($executionDate->toDateString(), self::TIME_ZONE);

        $states = $allStates->filter(
            fn ($state) => Carbon::parse($state->created_at)->setTimezone(self::TIME_ZONE)->isSameDay($executionDate)
        );

        $statesGroupedByDate = $states
            ->map(fn ($row) => $this->formatState($row))
            ->reverse()
            ->groupBy(fn ($state) => $state['created_at']);

        return $allStates
            ->map(fn ($state) => Carbon::parse($state->created_at)->setTimezone(self::TIME_ZONE)->toDateString())
            ->unique()
            ->mapWithKeys(fn ($date) => [$date => $statesGroupedByDate->get($date) ?? new Collection()]);
    }

    /**
     * Get scheduling executions for given date
     *
     * @param CarbonInterface $date
     *
     * @return Collection
     */
    public function getSchedulingExecutions(CarbonInterface $date): Collection
    {
        return $this->repository
            ->searchExecutionsByDate($date)
            ->map(fn ($row) => $this->formatExecution($row));
    }

    /**
     * @param \stdClass $row
     *
     * @return mixed[]
     */
    private function formatExecution(\stdClass $row): array
    {
        $result = (array) $row;
        $result['created_at'] = Carbon::parse($result['created_at'])->setTimezone(self::TIME_ZONE)->format('H:i:s');

        return $result;
    }

    /**
     * @param \stdClass $row
     *
     * @return mixed[]
     */
    private function formatState(\stdClass $row): array
    {
        $startAt = Carbon::parse($row->created_at)->setTimezone(self::TIME_ZONE);

        $result['id'] = $row->id;
        $result['created_at'] = $startAt->toDateString();
        $result['stats'] = $row->stats;
        $result['start_at'] = $startAt->toTimeString();

        return $result;
    }

    /**
     * @param int $stateId
     *
     * @return mixed[]
     */
    public function getStateData(int $stateId): array
    {
        $state = $this->repository->searchByIds([$stateId])->first();
        $scheduledRoutes = $this->repository
            ->searchScheduledRoutesByStateId($stateId)
            ->map(fn (\stdClass $route) => $this->formatRoute($route))
            ->toArray();

        return [
            'id' => $state->id,
            'office_id' => $state->office_id,
            'as_of_date' => $state->as_of_date,
            'pending_services' => array_values(json_decode($state->pending_services ?? '{}', true)),
            'scheduled_routes' => $scheduledRoutes,
            'stats' => json_decode($state->stats ?? '{}', true),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatRoute(\stdClass $route): array
    {
        return [
            'id' => $route->route_id,
            'details' => json_decode($route->details ?? '{}', true),
            'service_pro' => json_decode($route->service_pro ?? '{}', true),
            'appointments' => json_decode($route->appointments ?? '{}', true),
            'pending_services' => json_decode($route->pending_services ?? '{}', true),
        ];
    }

    /**
     * @param int $stateId
     *
     * @return mixed[]
     */
    public function getSchedulingStateInitialData(int $stateId): array
    {
        $state = $this->getStateData($stateId);

        return [
            'service_points' => $this->extractServicePoints($state),
            'service_pros' => $this->extractServicePro($state['scheduled_routes']),
        ];
    }

    /**
     * @param mixed[] $state
     *
     * @return mixed[]
     */
    private function extractServicePoints(array $state): array
    {
        $points = [];
        $pendingServices = array_values($state['pending_services']);

        foreach ($state['scheduled_routes'] as $scheduledRoute) {
            $pendingServices = array_merge($pendingServices, $scheduledRoute['pending_services']);
        }

        foreach ($pendingServices as $id => $pendingService) {
            $points[] = [
                'id' => $id,
                'lat' => $pendingService['location']['lat'],
                'lng' => $pendingService['location']['lng'],
                'priority' => $pendingService['priority'] ?? 1,
            ];
        }

        return $points;
    }

    /**
     * @param mixed[] $routes
     *
     * @return mixed[]
     */
    private function extractServicePro(array $routes): array
    {
        $result = [];

        foreach ($routes as $route) {
            $result[] = [
                'id' => $route['service_pro']['id'],
                'lat' => $route['service_pro']['location']['lat'],
                'lng' => $route['service_pro']['location']['lng'],
            ];
        }

        return $result;
    }
}
