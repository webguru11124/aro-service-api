<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes\CacheWrapper;

use App\Infrastructure\Services\Motive\Client\Resources\EmptyObject;
use Psr\SimpleCache\CacheInterface;

class QueryCacheWrapper
{
    private const HASH_ALGO = 'md5';

    public function __construct(
        private readonly AbstractPestRoutesCachedQuery $query,
        protected CacheInterface $cacheClient,
        protected int $ttl,
    ) {
    }

    /**
     * @param mixed ...$arguments
     *
     * @return bool
     */
    public function has(mixed ...$arguments): bool
    {
        return $this->hasInCache($arguments);
    }

    /**
     * @param mixed ...$arguments
     *
     * @return mixed
     */
    public function get(mixed ...$arguments): mixed
    {
        $cachedData = $this->getFromCache($arguments);

        if ($cachedData instanceof EmptyObject) {
            return null;
        }

        return $cachedData;
    }

    /**
     * @param mixed $data
     * @param mixed ...$arguments
     *
     * @return $this
     */
    public function put(mixed $data, mixed ...$arguments): self
    {
        $this->putToCache($data, $arguments);

        return $this;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return mixed
     */
    private function getFromCache(array $arguments): mixed
    {
        $key = $this->buildKey($arguments);

        return $this->cacheClient->get($key);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return bool
     */
    private function hasInCache(array $arguments): bool
    {
        $key = $this->buildKey($arguments);

        return $this->cacheClient->has($key);
    }

    /**
     * @param mixed $data
     * @param array<string, mixed> $arguments
     *
     * @return void
     */
    private function putToCache(mixed $data, array $arguments): void
    {
        $key = $this->buildKey($arguments);

        if ($data === null) {
            $data = EmptyObject::instance();
        }

        $this->cacheClient->set($key, $data, $this->ttl);
    }

    /**
     * @param array<string, mixed> $methodArguments
     *
     * @return string
     */
    private function buildKey(array $methodArguments): string
    {
        return class_basename($this->query) . '_' . hash(self::HASH_ALGO, serialize($methodArguments));
    }
}
