<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesRouteTemplatesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRouteTemplatesDataProcessor;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PestRoutesRouteTemplatesDataProcessorCacheWrapperTest extends TestCase
{
    private PestRoutesRouteTemplatesDataProcessor|MockInterface $wrappedMock;
    private PestRoutesRouteTemplatesDataProcessorCacheWrapper $cacheWrapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wrappedMock = Mockery::mock(PestRoutesRouteTemplatesDataProcessor::class);
        $this->cacheWrapper = new PestRoutesRouteTemplatesDataProcessorCacheWrapper($this->wrappedMock);
    }

    /**
     * @test
     */
    public function it_caches_method_results(): void
    {
        $officeId = 1;

        $this->wrappedMock
            ->shouldReceive('extract')
            ->once();

        $this->cacheWrapper->extract($officeId, new SearchServiceTypesParams());
        $this->cacheWrapper->extract($officeId, new SearchServiceTypesParams());
    }
}
