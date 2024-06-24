<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesServiceTypesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesServiceTypesDataProcessor;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Tests\TestCase;

class PestRoutesServiceTypesDataProcessorCacheWrapperTest extends TestCase
{
    private PestRoutesServiceTypesDataProcessor $pestRoutesServiceTypesDataProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pestRoutesServiceTypesDataProcessor = \Mockery::mock(PestRoutesServiceTypesDataProcessor::class);
        $this->pestRoutesServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect([]));
    }

    /**
     * @test
     */
    public function it_returns_uses_cache_correctly(): void
    {
        $pestRoutesServiceTypesDataProcessorCacheWrapper = new PestRoutesServiceTypesDataProcessorCacheWrapper($this->pestRoutesServiceTypesDataProcessor);

        $pestRoutesServiceTypesDataProcessorCacheWrapper->extract(1, new SearchServiceTypesParams());
        $pestRoutesServiceTypesDataProcessorCacheWrapper->extract(1, new SearchServiceTypesParams());
    }
}
