<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheWrapper
{
    private const HASH_ALGO = 'md5';
    private const CACHE_PREFIX = 'motiveClientCache_';

    public function __construct(
        private readonly AbstractResource $resource,
        protected CacheInterface $cacheClient,
        protected int $ttl,
        protected bool $forceUpdateCache = false
    ) {
    }

    /**
     * @param string $name
     * @param array<string, mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!$this->forceUpdateCache) {
            $cachedData = $this->findInCache($name, $arguments);

            if ($cachedData instanceof EmptyObject) {
                return null;
            }

            if ($cachedData !== null) {
                return $cachedData;
            }
        }

        $actualData = $this->resource->$name(...$arguments);

        $this->putToCache($name, $arguments, $actualData);

        return $actualData;
    }

    /**
     * @param string $name
     * @param array<mixed> $arguments
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function findInCache(string $name, array $arguments): mixed
    {
        $key = $this->buildKey($name, $arguments);

        return $this->cacheClient->get($key);
    }

    /**
     * @param string $name
     * @param array<string, mixed> $arguments
     * @param mixed $data
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function putToCache(string $name, array $arguments, mixed $data): void
    {
        $key = $this->buildKey($name, $arguments);

        if ($data === null) {
            $data = EmptyObject::instance();
        }

        $this->cacheClient->set($key, $data, $this->ttl);
    }

    /**
     * @param string $methodName
     * @param array<string, mixed> $methodArguments
     *
     * @return string
     */
    private function buildKey(string $methodName, array $methodArguments): string
    {
        $keyData = $methodName . serialize($methodArguments);

        return self::CACHE_PREFIX . hash(self::HASH_ALGO, $keyData);
    }

    /**
     * @param mixed $data
     * @param string $methodName
     * @param mixed ...$arguments
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function preload(mixed $data, string $methodName, mixed ...$arguments): self
    {
        $this->putToCache($methodName, $arguments, $data);

        return $this;
    }
}
