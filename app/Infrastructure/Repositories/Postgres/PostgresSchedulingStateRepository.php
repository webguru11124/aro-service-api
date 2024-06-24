<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\Contracts\Repositories\SchedulingStateRepository;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Infrastructure\Formatters\ScheduledRouteArrayFormatter;
use App\Infrastructure\Formatters\PendingServiceArrayFormatter;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Facades\DB;

class PostgresSchedulingStateRepository extends AbstractPostgresRepository implements SchedulingStateRepository
{
    public function __construct(
        private PendingServiceArrayFormatter $pendingServiceArrayFormatter,
        private ScheduledRouteArrayFormatter $scheduledRouteArrayFormatter,
    ) {
    }

    protected function getTableName(): string
    {
        return PostgresDBInfo::SCHEDULING_STATES_TABLE;
    }

    /**
     * Persists scheduling state
     *
     * @param SchedulingState $schedulingState
     *
     * @return void
     */
    public function save(SchedulingState $schedulingState): void
    {
        $this->getQueryBuilder()
            ->updateOrInsert(
                ['id' => $schedulingState->getId()],
                [
                    'as_of_date' => $schedulingState->getDate()->toDateString(),
                    'office_id' => $schedulingState->getOffice()->getId(),
                    'pending_services' => json_encode($this->getFormattedPendingServices($schedulingState)),
                    'stats' => json_encode($schedulingState->getStats()->toArray()),
                ],
            );

        $this->saveRouteDetails($schedulingState);
    }

    /**
     * @return mixed[]
     */
    private function getFormattedPendingServices(SchedulingState $schedulingState): array
    {
        return $schedulingState->getPendingServices()->map(
            fn (PendingService $pendingService) => $this->pendingServiceArrayFormatter->format($pendingService)
        )->toArray();
    }

    private function saveRouteDetails(SchedulingState $schedulingState): void
    {
        foreach ($schedulingState->getScheduledRoutes() as $route) {
            $formattedRoute = $this->scheduledRouteArrayFormatter->format($route);

            DB::table(PostgresDBInfo::SCHEDULED_ROUTE_DETAILS)
                ->updateOrInsert(
                    [
                        'route_id' => $route->getId(),
                        'scheduling_state_id' => $schedulingState->getId(),
                    ],
                    [
                        'details' => json_encode($formattedRoute['details']),
                        'appointments' => json_encode($formattedRoute['appointments']),
                        'pending_services' => json_encode($formattedRoute['pending_services']),
                        'service_pro' => json_encode($formattedRoute['service_pro']),
                    ],
                );
        }
    }

    public function getNextId(): int
    {
        $result = DB::select(sprintf(
            "SELECT NEXTVAL(pg_get_serial_sequence('%s', 'id')) as next_id;",
            $this->getTableName()
        ));

        return $result[0]->next_id;
    }
}
