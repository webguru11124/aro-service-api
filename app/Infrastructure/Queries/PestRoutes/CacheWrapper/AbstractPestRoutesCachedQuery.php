<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes\CacheWrapper;

use App\Infrastructure\Queries\PestRoutes\Params\AbstractCachedQueryParams;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\Entity as PestRoutesEntity;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;

abstract class AbstractPestRoutesCachedQuery
{
    protected bool $forceUpdateCache = false;

    private QueryCacheWrapper|null $cache = null;

    public function __construct(
        private PestRoutesClient $client,
        protected OfficesDataProcessor $officesDataProcessor,
        protected CacheInterface $cacheClient
    ) {
    }

    /**
     * Initializes cache.
     * Once cache initialized it will be used for storing resource data.
     *
     * @param int $ttl
     * @param bool $forceUpdateCache
     *
     * @return self
     */
    public function cached(int $ttl, bool $forceUpdateCache = false): self
    {
        if ($this->cache === null) {
            $this->cache = new QueryCacheWrapper($this, $this->cacheClient, $ttl);
        }

        $this->forceUpdateCache = $forceUpdateCache;

        return $this;
    }

    protected function getClient(): PestRoutesClient
    {
        return $this->client;
    }

    protected function getOffice(int $officeId): PestRoutesOffice
    {
        return $this->officesDataProcessor->extract($officeId, new SearchOfficesParams(
            officeId: $officeId
        ))->first();
    }

    protected function getResources(int $officeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $this->hasCachedData($officeId, $start)
            ? $this->getDataFromCache($officeId, $start, $end)
            : $this->getDataFromSource($officeId, $start, $end);
    }

    abstract public function get(AbstractCachedQueryParams $params): Collection;

    /**
     * Implement this method to fetch resources from PestRoutes source
     *
     * @param int $officeId
     * @param CarbonInterface $start
     * @param CarbonInterface $end
     *
     * @return Collection<PestRoutesEntity>
     */
    abstract protected function fetchResourcesFromSource(int $officeId, CarbonInterface $start, CarbonInterface $end): Collection;

    /**
     * Implement this method to get date from PestRoutes entity so data can be stored in cache
     *
     * @param PestRoutesEntity $resource
     *
     * @return CarbonInterface
     */
    abstract protected function getResourceDate(PestRoutesEntity $resource): CarbonInterface;

    private function getDataFromSource(int $officeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $routes = $this->fetchResourcesFromSource($officeId, $start, $end);

        $this->putResourcesIntoCache($routes, $officeId, $start, $end);

        return $routes;
    }

    private function getDataFromCache(int $officeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $resources = collect();

        for ($date = $start->clone(); $date->lte($end); $date->addDay()) {
            $cachedData = $this->getFromCache($officeId, $date->toDateString());

            if ($cachedData instanceof Collection) {
                $resources = $resources->merge($cachedData);
            }
        }

        return $resources;
    }

    private function putResourcesIntoCache(Collection $routes, int $officeId, CarbonInterface $start, CarbonInterface $end): void
    {
        for ($date = $start->clone(); $date->lte($end); $date->addDay()) {
            $routesOnDate = $routes->filter(
                fn (PestRoutesEntity $resource) => $this->getResourceDate($resource)->toDateString() == $date->toDateString()
            );
            $this->putToCache($routesOnDate, $officeId, $date->toDateString());
        }
    }

    private function hasCachedData(int $officeId, CarbonInterface $startDate): bool
    {
        return $this->isCacheEnable() && $this->cache->has($officeId, $startDate->toDateString());
    }

    private function isCacheEnable(): bool
    {
        return $this->cache !== null && !$this->forceUpdateCache;
    }

    private function putToCache(mixed $data, mixed ...$arguments): void
    {
        $this->cache?->put($data, ...$arguments);
    }

    private function getFromCache(mixed ...$arguments): mixed
    {
        return $this->cache?->get(...$arguments);
    }
}
