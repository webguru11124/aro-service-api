<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesOfficesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesOfficesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\OfficeData;

class PestRoutesOfficesDataProcessorCacheWrapperTest extends TestCase
{
    private PestRoutesOfficesDataProcessorCacheWrapper $wrapper;
    private PestRoutesOfficesDataProcessor|MockInterface $wrappeeMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wrappeeMock = \Mockery::mock(PestRoutesOfficesDataProcessor::class);

        $this->wrapper = new PestRoutesOfficesDataProcessorCacheWrapper($this->wrappeeMock);
    }

    /**
     * @test
     */
    public function it_caches_data(): void
    {
        $officeId = $this->faker->randomNumber(2);
        $params = new SearchOfficesParams(
            officeId: $officeId
        );

        $offices = OfficeData::getTestData();

        $this->wrappeeMock->shouldReceive('extract')
            ->withArgs([$officeId, $params])
            ->once()
            ->andReturn($offices);

        $result1 = $this->wrapper->extract($officeId, $params);
        $result2 = $this->wrapper->extract($officeId, $params);

        $this->assertSame($offices, $result1);
        $this->assertSame($offices, $result2);
    }
}
