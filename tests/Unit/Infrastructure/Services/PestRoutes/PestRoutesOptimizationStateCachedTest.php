<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes;

use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateCached;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\OptimizationStateFactory;

class PestRoutesOptimizationStateCachedTest extends TestCase
{
    private PestRoutesOptimizationStateResolver|MockInterface $pestRoutesOptimizationStateResolverMock;
    private PestRoutesOptimizationStateCached $resolver;
    private const CACHE_TTL = 60;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pestRoutesOptimizationStateResolverMock = Mockery::mock(PestRoutesOptimizationStateResolver::class);

        $this->resolver = new PestRoutesOptimizationStateCached(
            $this->pestRoutesOptimizationStateResolverMock
        );
    }

    /**
     * @test
     */
    public function it_resolves_and_caches_optimization_state(): void
    {
        $date = Carbon::now();
        $office = OfficeFactory::make();
        $optimizationParams = new OptimizationParams();

        $cacheKey = 'pre_' . class_basename($this->resolver) . '_' . md5(serialize([
            'date' => $date->toDateString(),
            'office' => $office->getId(),
            'optimizationParams' => $optimizationParams,
        ]));

        $cachedState = OptimizationStateFactory::make();

        $this->pestRoutesOptimizationStateResolverMock
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($cachedState);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($receivedCacheKey, $receivedTtl, $callback) use ($cacheKey, $cachedState) {
                return $receivedCacheKey === $cacheKey
                    && $receivedTtl->diffInMinutes(Carbon::now()->addMinutes(self::CACHE_TTL)) < 1
                    && $callback() === $cachedState;
            })
            ->andReturn($cachedState);

        $this->resolver->resolve($date, $office, $optimizationParams);
    }

    /**
     * @test
     */
    public function it_retrieves_optimization_state_from_cache_if_already_cached(): void
    {
        $date = Carbon::now();
        $office = OfficeFactory::make();
        $optimizationParams = new OptimizationParams();

        $cacheKey = 'pre_' . class_basename($this->resolver) . '_' . md5(serialize([
            'date' => $date->toDateString(),
            'office' => $office->getId(),
            'optimizationParams' => $optimizationParams,
        ]));

        $cachedState = OptimizationStateFactory::make();

        Cache::put($cacheKey, $cachedState, Carbon::now()->addMinutes(self::CACHE_TTL));

        $this->pestRoutesOptimizationStateResolverMock
            ->shouldNotReceive('resolve');

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($receivedCacheKey, $receivedTtl, $callback) use ($cacheKey, $cachedState) {
                return $receivedCacheKey === $cacheKey
                    && $receivedTtl->diffInMinutes(Carbon::now()->addMinutes(self::CACHE_TTL)) < 1;
            })
            ->andReturn($cachedState);

        $result = $this->resolver->resolve($date, $office, $optimizationParams);

        $this->assertSame($cachedState, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->pestRoutesOptimizationStateResolverMock);
        unset($this->resolver);
    }
}
