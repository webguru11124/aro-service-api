<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Services;

use App\Domain\Contracts\Services\RouteCompletionStatsService;
use App\Domain\Contracts\Services\RouteDrivingStatsService;
use App\Domain\Contracts\Services\VehicleTrackingDataService;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\Entities\FleetRouteState;
use Carbon\Carbon;

class FleetRouteStatsService
{
    private FleetRouteState $fleetRouteState;

    public function __construct(
        private RouteCompletionStatsService $completionStatsService,
        private RouteDrivingStatsService $drivingStatsService,
        private VehicleTrackingDataService $trackingDataService,
    ) {
    }

    /**
     * Updates fleet routes actual stats like: route adherence, driving and complete service stats
     *
     * @param FleetRouteState $fleetRouteState
     *
     * @return FleetRouteState
     */
    public function updateActualStats(FleetRouteState $fleetRouteState): FleetRouteState
    {
        $this->fleetRouteState = $fleetRouteState;

        $this->resolveRoutesTrackingData();

        if ($this->fleetRouteState->canUpdateStats()) {
            $this->resolveRoutesCompletionStats();
            $this->resolveRoutesDrivingStats();
        }

        return $this->fleetRouteState;
    }

    private function resolveRoutesCompletionStats(): void
    {
        $this->completionStatsService->updateCompletionStats($this->fleetRouteState);
    }

    private function resolveRoutesDrivingStats(): void
    {
        $userIds = $this->fleetRouteState->getFleetRoutes()->map(
            fn (FleetRoute $fleetRoute) => $fleetRoute->getServicePro()->getWorkdayId()
        )->filter()->toArray();

        $stats = $this->drivingStatsService->get($userIds, $this->fleetRouteState->getDate());

        foreach ($this->fleetRouteState->getFleetRoutes() as $fleetRoute) {
            $drivingStats = $stats->get($fleetRoute->getServicePro()->getWorkdayId());

            if ($drivingStats === null) {
                continue;
            }

            $fleetRoute->setDrivingStats($drivingStats);
        }
    }

    private function resolveRoutesTrackingData(): void
    {
        if (!$this->isToday()) {
            return;
        }

        $userIds = $this->fleetRouteState->getFleetRoutes()->map(
            fn (FleetRoute $fleetRoute) => $fleetRoute->getServicePro()->getWorkdayId()
        )->toArray();

        $trackingData = $this->trackingDataService->get(
            $userIds,
            $this->fleetRouteState->getDate()
        );

        foreach ($this->fleetRouteState->getFleetRoutes() as $fleetRoute) {
            $data = $trackingData->get($fleetRoute->getServicePro()->getWorkdayId());

            if ($data === null) {
                continue;
            }

            $fleetRoute->setTrackingData($data);
        }
    }

    private function isToday(): bool
    {
        $nowInOffice = Carbon::now($this->fleetRouteState->getDate()->getTimezone());

        return $nowInOffice->isSameDay($this->fleetRouteState->getDate());
    }
}
