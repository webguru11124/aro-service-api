<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\Scheduling\Entities\Route;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface GetRoutesByOfficeAndDateQuery
{
    /**
     * Returns regular routes for specified office and date
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<Route>
     */
    public function get(Office $office, CarbonInterface $date): Collection;
}
