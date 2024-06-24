<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface VehicleTrackingDataService
{
    /**
     * @param string[] $userIds
     * @param CarbonInterface $date
     *
     * @return Collection<string, RouteTrackingData>
     */
    public function get(array $userIds, CarbonInterface $date): Collection;
}
