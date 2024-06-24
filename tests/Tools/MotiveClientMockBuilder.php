<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Infrastructure\Services\Motive\Client\Client;
use App\Infrastructure\Services\Motive\Client\Resources\CacheWrapper;
use Closure;
use Mockery;
use Mockery\MockInterface;
use Throwable;

class MotiveClientMockBuilder
{
    private int $times = 1;
    private string $resourceCallMethod;
    private string $resource;
    private string $method;
    private mixed $outcome = null;
    private array|Closure $expectedArgs;
    private bool $cached = false;
    private int|null $cacheTtl = null;

    public function __construct(private MockInterface|null $clientMock = null)
    {
    }

    public function times(int $times): self
    {
        $this->times = $times;

        return $this;
    }

    public function resource(string $callMethod, string $resource): self
    {
        $this->resourceCallMethod = $callMethod;
        $this->resource = $resource;

        return $this;
    }

    public function cached(int|null $ttl = null): self
    {
        $this->cached = true;
        $this->cacheTtl = $ttl;

        return $this;
    }

    public function withArgs(array|Closure $args): self
    {
        $this->expectedArgs = $args;

        return $this;
    }

    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function willReturn(mixed $outcome): self
    {
        $this->outcome = $outcome;

        return $this;
    }

    public function willThrow(Throwable $throwable): self
    {
        $this->outcome = $throwable;

        return $this;
    }

    public function mock(): MockInterface|Client
    {
        if ($this->outcome instanceof Throwable) {
            return $this->mockClientThatThrowsError($this->outcome);
        }

        if ($this->cached) {
            return $this->mockCached();
        }

        $resourceMock = Mockery::mock($this->resource);
        $expectation = $resourceMock->shouldReceive($this->method);

        if (!empty($this->expectedArgs)) {
            $expectation->withArgs($this->expectedArgs);
        }

        $expectation
            ->andReturn($this->outcome)
            ->times($this->times);

        $clientMock = $this->clientMock ?: Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive($this->resourceCallMethod)
            ->andReturn($resourceMock)
            ->times($this->times);

        return $clientMock;
    }

    public function preload(array|Closure $args): MockInterface|Client
    {
        $cacheWrapperMock = Mockery::mock(CacheWrapper::class);
        $cacheWrapperMock
            ->shouldReceive('preload')
            ->withArgs($args)
            ->andReturnSelf();

        $resourceMock = Mockery::mock($this->resource);
        $resourceExpectation = $resourceMock
            ->shouldReceive('cached')
            ->andReturn($cacheWrapperMock);

        if ($this->cacheTtl !== null) {
            $resourceExpectation->with($this->cacheTtl);
        }

        $clientMock = $this->clientMock ?: Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive($this->resourceCallMethod)
            ->andReturn($resourceMock);

        return $clientMock;
    }

    private function mockClientThatThrowsError(Throwable $exception): MockInterface|Client
    {
        $resourceMock = Mockery::mock($this->resource);
        $resourceMock
            ->shouldReceive($this->method)
            ->andThrow($exception);

        $clientMock = $this->clientMock ?: Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive($this->resourceCallMethod)
            ->andReturn($resourceMock);

        return $clientMock;
    }

    private function mockCached(): MockInterface|Client
    {
        $cacheWrapperMock = Mockery::mock(CacheWrapper::class);
        $expectation = $cacheWrapperMock->shouldReceive($this->method);

        if (!empty($this->expectedArgs)) {
            $expectation->withArgs($this->expectedArgs);
        }

        $expectation
            ->andReturn($this->outcome)
            ->times($this->times);

        $resourceMock = Mockery::mock($this->resource);
        $resourceExpectation = $resourceMock
            ->shouldReceive('cached')
            ->andReturn($cacheWrapperMock);

        if ($this->cacheTtl !== null) {
            $resourceExpectation->with($this->cacheTtl);
        }

        $clientMock = $this->clientMock ?: Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive($this->resourceCallMethod)
            ->andReturn($resourceMock)
            ->times($this->times);

        return $clientMock;
    }
}
