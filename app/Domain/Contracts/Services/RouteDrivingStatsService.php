<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface RouteDrivingStatsService
{
    /**
     * Returns driving stats for the given user ids and date
     *
     * @param string[] $userIds
     * @param CarbonInterface $date
     *
     * @return Collection<RouteDrivingStats>
     */
    public function get(array $userIds, CarbonInterface $date): Collection;
}
