<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\CacheWrapper\AbstractPestRoutesCachedQuery;
use App\Infrastructure\Queries\PestRoutes\Params\AbstractCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\Params\SpotsCachedQueryParams;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Entity as PestRoutesEntity;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PestRoutesSpotsCachedQuery extends AbstractPestRoutesCachedQuery
{
    private PestRoutesOffice $pestRoutesOffice;
    private SpotsCachedQueryParams $params;

    /**
     * Retrieves spots for given office ID and date or date interval.
     *
     * @param AbstractCachedQueryParams|SpotsCachedQueryParams $params
     *
     * @return Collection<PestRoutesSpot>
     */
    public function get(AbstractCachedQueryParams|SpotsCachedQueryParams $params): Collection
    {
        /** @phpstan-ignore-next-line  */
        $this->params = $params;
        $this->resolvePestRoutesOffice();

        return $this->getResources(
            $params->officeId,
            $params->startDate->clone(),
            $params->endDate !== null ? $params->endDate->clone() : $params->startDate->clone()
        );
    }

    protected function fetchResourcesFromSource(int $officeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $pestRoutesSpots = $this->getClient()->office($officeId)
            ->spots()
            ->searchAsync(
                new SearchSpotsParams(
                    officeIds: [$officeId],
                    date: DateFilter::between(
                        $start->clone()->startOfDay()->toDateTime(),
                        $end->clone()->endOfDay()->toDateTime()
                    ),
                    apiCanSchedule: $this->params->apiCanSchedule,
                ),
                new PestRoutesCollection([$this->pestRoutesOffice])
            );

        /** @var Collection<PestRoutesSpot> $pestRoutesSpots */
        $pestRoutesSpots = new Collection($pestRoutesSpots->items);

        return $pestRoutesSpots->values();
    }

    protected function getResourceDate(PestRoutesEntity $resource): CarbonInterface
    {
        /** @var PestRoutesSpot $resource */
        return Carbon::instance($resource->start);
    }

    private function resolvePestRoutesOffice(): void
    {
        $this->pestRoutesOffice = $this->getOffice($this->params->officeId);
    }
}
