<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Tracking\Entities\FleetRouteState;
use Carbon\CarbonInterface;

interface FleetRouteStateRepository
{
    /**
     * Returns fleet route state for specified office ID on date
     *
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return FleetRouteState|null
     */
    public function findByOfficeIdAndDate(int $officeId, CarbonInterface $date): FleetRouteState|null;
}
