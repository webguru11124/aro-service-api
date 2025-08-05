<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Infrastructure\Repositories\Postgres\PostgresOptimizationDataRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class OptimizationDataService
{
    private const TIME_ZONE = 'America/Denver';

    public function __construct(
        private PostgresOptimizationDataRepository $optimizationDataRepository,
    ) {
    }

    /**
     * Returns list of offices
     *
     * @return array<int, string>
     */
    public function getOffices(): array
    {
        return $this->optimizationDataRepository->findOffices()
            ->mapWithKeys(function ($office) {
                return [$office->office_id => $office->office];
            })
            ->toArray();
    }

    /**
     * Return a list of optimization runs for given date per each office
     *
     * @param CarbonInterface $date
     *
     * @return Collection
     */
    public function getOptimizationExecutions(CarbonInterface $date): Collection
    {
        return $this->optimizationDataRepository
            ->searchExecutionsByDate($date)
            ->map(fn ($row) => $this->formatExecution($row));
    }

    /**
     * @param \stdClass $row
     *
     * @return array<string, mixed>
     */
    private function formatExecution(\stdClass $row): array
    {
        $result = (array) $row;
        $createdAt = !empty($result['created_at'])
            ? Carbon::createFromTimestamp($result['created_at'])
            : Carbon::parse($result['recorded_at']);
        $result['created_at'] = $createdAt->setTimezone(self::TIME_ZONE)->format('H:i:s');

        return $result;
    }

    /**
     * Returns optimization states overview for given office and date
     *
     * @param int $officeId
     * @param CarbonInterface $optimizationDate
     * @param CarbonInterface|null $executionDate
     *
     * @return Collection
     */
    public function getOptimizationOverview(
        int $officeId,
        CarbonInterface $optimizationDate,
        CarbonInterface|null $executionDate = null
    ): Collection {
        $allStates = $this->optimizationDataRepository->findStatesIdsByOfficeIdAndDate($officeId, $optimizationDate);

        if ($allStates->isEmpty()) {
            return new Collection();
        }

        $executionDate = is_null($executionDate)
            ? Carbon::parse($allStates->first()->created_at)->setTimezone(self::TIME_ZONE)
            : Carbon::parse($executionDate->toDateString(), self::TIME_ZONE);

        $preStateIds = $allStates->filter(
            fn ($state) => Carbon::parse($state->created_at)->setTimezone(self::TIME_ZONE)->isSameDay($executionDate)
        )->pluck('id')->toArray();

        $states = $this->resolveFullOptimizationStates($preStateIds);

        $statesGroupedByDate = $states
            ->map(fn ($row) => $this->formatState($row))
            ->groupBy(fn ($state) => $state['created_at'])->reverse();

        return $allStates
            ->map(fn ($state) => Carbon::parse($state->created_at)->setTimezone(self::TIME_ZONE)->toDateString())
            ->unique()
            ->mapWithKeys(fn ($date) => [$date => $statesGroupedByDate->get($date) ?? new Collection()]);
    }

    /**
     * @param mixed[] $row
     *
     * @return mixed[]
     */
    private function formatState(array $row): array
    {
        $preState = $row['pre_state']['state'];

        $startAt = !empty($row['pre_state']['created_at'])
            ? Carbon::parse($row['pre_state']['created_at'])->setTimezone(self::TIME_ZONE)
            : Carbon::parse($preState['created_at'])->setTimezone(self::TIME_ZONE);
        $result['created_at'] = $startAt->toDateString();
        $result['start_at'] = $startAt->toTimeString();
        $result['start_timestamp'] = $startAt->clone()->subSecond()->getTimestampMs();
        $result['end_timestamp'] = Carbon::parse($preState['created_at'])->addMinute()->setTimezone(self::TIME_ZONE)->getTimestampMs();

        $result['pre_state_id'] = $row['pre_state']['id'];
        $result['engine'] = $preState['engine'];
        $result['overbooked_appointments_count'] = count($preState['unassigned_appointments']);
        $result['params'] = $preState['params'] ?? [];
        $result['routes'] = $row['pre_state']['routes'];
        $result['serialized_state_id'] = $row['pre_state']['serialized_state_id'] ?? null;

        $postState = $row['post_state'] ?? ($row['sim_states'][0] ?? null);

        if (!empty($postState)) {
            $endAt = Carbon::parse($postState['created_at'])->setTimezone(self::TIME_ZONE);
            $result['result_state_id'] = $postState['id'];
            $result['processing'] = $endAt->diffInSeconds($startAt);
            $result['total_assigned_appointments'] = $postState['stats']['total_assigned_appointments'];
            $result['metrics'] = $postState['metrics'];
            $result['unassigned_appointments_count'] = count($postState['state']['unassigned_appointments']);
            $result['end_timestamp'] = $endAt->addSecond()->getTimestampMs();
            $result['routes'] = $postState['routes'];
            $result['rules'] = $postState['rules'];
            $result['weather_forecast'] = $postState['weather_forecast'];
            $result['stats'] = $postState['stats'];
        }

        $result['simulations'] = !empty($row['sim_states']);

        return $result;
    }

    /**
     * @param int $stateId
     *
     * @return mixed[]
     */
    public function getSingleStateData(int $stateId): array
    {
        return $this->optimizationDataRepository->searchByIds([$stateId])->first();
    }

    /**
     * @param int $stateId
     *
     * @return mixed[]
     */
    public function getOptimizationStateDetails(int $stateId): array
    {
        return $this->resolveFullOptimizationStates([$stateId])->first();
    }

    /**
     * @param int[] $stateIds
     *
     * @return Collection
     */
    private function resolveFullOptimizationStates(array $stateIds): Collection
    {
        $preStates = $this->optimizationDataRepository->searchByIds($stateIds);
        $relatedStates = $this->optimizationDataRepository->searchByPreviousStateIds($stateIds);

        $postStates = $relatedStates->filter(fn ($state) => $state['status'] === OptimizationStatus::POST)
            ->keyBy('previous_state_id');
        $planStates = $relatedStates->filter(fn ($state) => $state['status'] === OptimizationStatus::PLAN)
            ->keyBy('previous_state_id');
        $simStates = $relatedStates->filter(fn ($state) => $state['status'] === OptimizationStatus::SIMULATION)
            ->groupBy('previous_state_id');

        return $preStates->map(function ($preState) use ($postStates, $planStates, $simStates) {
            $result['pre_state'] = $preState;
            $preStateId = $preState['id'];

            if ($postStates->has($preStateId)) {
                $result['post_state'] = $postStates->get($preStateId);
            }
            if ($planStates->has($preStateId)) {
                $result['plan_state'] = $planStates->get($preStateId);
            }
            if ($simStates->has($preStateId)) {
                $result['sim_states'] = $simStates->get($preStateId);
            }

            return $result;
        });
    }

    /**
     * @param int $stateId
     *
     * @return mixed[]
     */
    public function getOptimizationStateWithSimulations(int $stateId): array
    {
        $preState = $this->optimizationDataRepository->searchByIds([$stateId])->first();
        $relatedStates = $this->optimizationDataRepository->searchByPreviousStateIds([$stateId]);

        $postState = $relatedStates->filter(fn ($state) => $state['status'] == OptimizationStatus::POST)->first();
        $simStates = $relatedStates->filter(fn ($state) => $state['status'] == OptimizationStatus::SIMULATION)
            ->groupBy('previous_state_id');

        return [
            'pre_state' => $preState,
            'post_state' => $postState,
            'sim_states' => $simStates->get($stateId)?->toArray(),
        ];
    }
}
