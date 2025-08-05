<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources;

use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use App\Infrastructure\Services\Motive\Client\Resources\CacheWrapper;
use Psr\SimpleCache\CacheInterface;

trait ResourceCanBeCached
{
    /**
     * @test
     */
    public function it_returns_cached_wrapper_if_cache_client_is_set(): void
    {
        $resourceClass = $this->getTestedResourceClass();

        /** @var AbstractResource $resource */
        $resource = new $resourceClass(
            \Mockery::mock(HttpClient::class),
            \Mockery::mock(CacheInterface::class)
        );

        $result = $resource->cached(random_int(100, 999));
        $this->assertInstanceOf(CacheWrapper::class, $result);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_it_called_cached_method_when_cache_client_not_set(): void
    {
        $resourceClass = $this->getTestedResourceClass();

        /** @var AbstractResource $resource */
        $resource = new $resourceClass(
            \Mockery::mock(HttpClient::class),
        );

        $this->expectException(MotiveClientException::class);

        $resource->cached(random_int(100, 999));
    }

    abstract public static function assertInstanceOf(string $expected, mixed $actual, string $message = ''): void;

    abstract private function getTestedResourceClass(): string;
}
