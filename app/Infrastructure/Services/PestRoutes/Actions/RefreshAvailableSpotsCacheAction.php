<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Actions;

use App\Infrastructure\Queries\PestRoutes\Params\RoutesCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\Params\SpotsCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\PestRoutesRoutesCachedQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesSpotsCachedQuery;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RefreshAvailableSpotsCacheAction
{
    private const int DEFAULT_CACHE_TTL = 60 * 5; // 5 min
    private const int DEFAULT_DATES_RANGE = 14;

    public function __construct(
        private readonly PestRoutesSpotsCachedQuery $routeSpotsQuery,
        private readonly PestRoutesRoutesCachedQuery $routesQuery,
    ) {
    }

    /**
     * It refreshes the cache for available spots for the given office and period.
     *
     * @param int $officeId
     * @param CarbonInterface|null $startDate
     * @param CarbonInterface|null $endDate
     * @param int|null $ttl
     *
     * @return void
     */
    public function execute(
        int $officeId,
        CarbonInterface|null $startDate = null,
        CarbonInterface|null $endDate = null,
        int|null $ttl = null,
    ): void {
        $startDate = $startDate ?? Carbon::tomorrow()->addDay();
        $endDate = $endDate ?? $startDate->clone()->addDays(self::DEFAULT_DATES_RANGE);

        $this->routesQuery
            ->cached($ttl ?? self::DEFAULT_CACHE_TTL, true)
            ->get(new RoutesCachedQueryParams($officeId, $startDate, $endDate));

        $this->routeSpotsQuery
            ->cached($ttl ?? self::DEFAULT_CACHE_TTL, true)
            ->get(new SpotsCachedQueryParams($officeId, $startDate, $endDate));
    }
}
