<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Tracking\Entities\FleetRouteState;

interface RouteCompletionStatsService
{
    /**
     * Calculates and update completion stats, such as route adherence and number of complete appointments.
     *
     * @param FleetRouteState $fleetRouteState
     *
     * @return void
     */
    public function updateCompletionStats(FleetRouteState $fleetRouteState): void;
}
