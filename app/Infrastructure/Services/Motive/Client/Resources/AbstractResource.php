<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;

class AbstractResource
{
    protected const HEADER_TIME_ZONE = 'X-Time-Zone';

    public function __construct(
        protected HttpClient $httpClient,
        protected CacheInterface|null $cacheClient = null
    ) {
    }

    protected function getBaseUrl(): string
    {
        return config('motive.base_api_url');
    }

    /**
     * Returns Cache wrapper that utilize same methods as a current resource class
     * but uses cache to store and fetch method outcome.
     * The method requires TTL argument (in seconds) that defines the time data lives in cache.
     *
     * @param int $ttl
     * @param bool $forceUpdateCache
     *
     * @return CacheWrapper
     * @throws MotiveClientException
     */
    public function cached(int $ttl, bool $forceUpdateCache = false): CacheWrapper
    {
        if ($this->cacheClient === null) {
            throw new MotiveClientException('Cache client does not set.');
        }

        return new CacheWrapper($this, $this->cacheClient, $ttl, $forceUpdateCache);
    }

    /**
     * @param string $endpoint
     * @param callable $mapCallback
     * @param HttpParams|null $params
     * @param array<string, string> $headers
     *
     * @return AbstractEntity|null
     */
    public function get(
        string $endpoint,
        callable $mapCallback,
        HttpParams|null $params = null,
        array $headers = []
    ): AbstractEntity|null {
        $object = $this->httpClient->get(
            endpoint: $endpoint,
            params: $params,
            headers: $headers
        );

        return $mapCallback($object);
    }

    /**
     * @param string $endpoint
     * @param callable $mapCallback
     * @param PaginationParams|null $params
     * @param array<string, string> $headers
     *
     * @return Collection<AbstractEntity>
     */
    protected function getWithPagination(
        string $endpoint,
        callable $mapCallback,
        PaginationParams|null $params = null,
        array $headers = []
    ): Collection {
        $object = $this->httpClient->get(
            endpoint: $endpoint,
            params: $params,
            headers: $headers
        );

        if ($params->isPaginationSet()) {
            return $mapCallback($object);
        }

        /** @var Collection<AbstractEntity> $collection */
        $collection = $mapCallback($object);

        for ($i = 2; $i <= $this->getNumberOfPages($object); $i++) {
            $object = $this->httpClient->get(
                endpoint: $endpoint,
                params: $params->setPage($i),
                headers: $headers
            );

            $collection = $collection->merge($mapCallback($object));
        }

        return $collection;
    }

    private function getNumberOfPages(object $rawObject): int
    {
        $pagination = $rawObject->pagination;
        $perPage = $pagination->per_page;
        $total = $pagination->total;

        return (int) ceil($total / $perPage);
    }
}
