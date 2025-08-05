<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\CacheWrapper\AbstractPestRoutesCachedQuery;
use App\Infrastructure\Queries\PestRoutes\Params\AbstractCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\Params\RoutesCachedQueryParams;
use Aptive\PestRoutesSDK\Entity as PestRoutesEntity;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PestRoutesRoutesCachedQuery extends AbstractPestRoutesCachedQuery
{
    /**
     * Retrieves routes for given date or date interval and office ID.
     *
     * @param AbstractCachedQueryParams|RoutesCachedQueryParams $params
     *
     * @return Collection<PestRoutesRoute>
     */
    public function get(AbstractCachedQueryParams|RoutesCachedQueryParams $params): Collection
    {
        return $this->getResources(
            $params->officeId,
            $params->startDate->clone(),
            $params->endDate !== null ? $params->endDate->clone() : $params->startDate->clone()
        );
    }

    /**
     * @return Collection<PestRoutesRoute>
     */
    protected function fetchResourcesFromSource(int $officeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $pestRoutesRoutes = $this->getClient()->office($officeId)
            ->routes()
            ->includeData()
            ->search(new SearchRoutesParams(
                officeIds: [$officeId],
                date: DateFilter::between(
                    $start->clone()->startOfDay()->toDateTime(),
                    $end->clone()->endOfDay()->toDateTime()
                )
            ))
            ->all();

        /** @var Collection<PestRoutesRoute> $pestRoutesRoutes */
        $pestRoutesRoutes = new Collection($pestRoutesRoutes->items);

        return $pestRoutesRoutes;
    }

    protected function getResourceDate(PestRoutesEntity $resource): CarbonInterface
    {
        /** @var PestRoutesRoute $resource */
        return Carbon::instance($resource->date);
    }
}
