<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\Contracts\Repositories\TreatmentStateRepository;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PostgresTreatmentStateRepository implements TreatmentStateRepository
{
    /**
     * Updates serviced route details and treatment state
     *
     * @param TreatmentState $state
     *
     * @return void
     */
    public function save(TreatmentState $state): void
    {
        $updatedAt = Carbon::now($state->getDate()->getTimezone());
        $date = $state->getDate()->toDateString();

        DB::table(PostgresDBInfo::TREATMENT_STATE_TABLE)
            ->updateOrInsert(
                [
                    'office_id' => $state->getId()->officeId,
                    'as_of_date' => $date,
                ],
                [
                    'stats' => json_encode($state->getSummary()->toArray()),
                    'updated_at' => $updatedAt->toISOString(),
                ]
            );

        $stateId = $this->getStateId($state->getId()->officeId, $date);
        $this->saveRoutes($stateId, $state);
    }

    private function saveRoutes(int $stateId, TreatmentState $state): void
    {
        $state->getServicedRoutes()->each(
            function (ServicedRoute $route) use ($stateId) {
                DB::table(PostgresDBInfo::SERVICED_ROUTE_DETAILS_TABLE)
                    ->updateOrInsert(
                        [
                            'treatment_state_id' => $stateId,
                            'route_id' => $route->getId(),
                        ],
                        [
                            'service_pro' => $this->formatServicePro($route->getServicePro()),
                            'stats' => json_encode(array_merge(
                                $route->getCompletionStats()->toArray(),
                                $route->getDrivingStats() ? $route->getDrivingStats()->toArray() : [],
                            )),
                        ]
                    );
            }
        );
    }

    private function formatServicePro(ServicePro $servicePro): string
    {
        return json_encode([
            'id' => $servicePro->getId(),
            'name' => $servicePro->getName(),
            'workday_id' => $servicePro->getWorkdayId(),
        ]);
    }

    private function getStateId(int $officeId, string $date): int|null
    {
        return DB::table(PostgresDBInfo::TREATMENT_STATE_TABLE)
            ->select('id')
            ->where('office_id', $officeId)
            ->where('as_of_date', $date)
            ->first()?->id;
    }
}
