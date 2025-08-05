<?php

declare(strict_types=1);

namespace App\Infrastructure\CacheWrapper;

use App\Infrastructure\Exceptions\CachedWrapperException;
use Illuminate\Support\Facades\Cache;

abstract class AbstractCachedWrapper
{
    private const HASH_ALGO = 'md5';

    protected mixed $wrapped;

    protected function cached(string $methodName, mixed ...$methodArguments): mixed
    {
        return Cache::remember(
            $this->buildKey($methodName, $methodArguments),
            $this->getCacheTtl($methodName),
            fn () => $this->getWrapped()->$methodName(...$methodArguments)
        );
    }

    /**
     * @param string $methodName
     * @param mixed[] $methodArguments
     *
     * @return string
     */
    protected function buildKey(string $methodName, array $methodArguments): string
    {
        $prefix = $this->getPrefix();
        $class = static::class;

        return $prefix . hash(self::HASH_ALGO, $class . $methodName . serialize($methodArguments));
    }

    /**
     * Returns wrapped object.
     */
    protected function getWrapped(): mixed
    {
        $selfImplementations = class_implements($this);
        $wrappedImplementations = class_implements($this->wrapped);

        $commonInterfaces = array_intersect($selfImplementations, $wrappedImplementations);

        if (empty($commonInterfaces)) {
            throw CachedWrapperException::wrappedClassImplementationMismatch();
        }

        return $this->wrapped;
    }

    abstract protected function getCacheTtl(string $methodName): int;

    abstract protected function getPrefix(): string;
}
