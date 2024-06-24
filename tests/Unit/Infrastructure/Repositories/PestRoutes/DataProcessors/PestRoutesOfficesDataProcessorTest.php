<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesOfficesDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\OfficeData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesOfficesDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    /**
     * @test
     */
    public function it_extracts_offices(): void
    {
        $searchOfficesParamsMock = \Mockery::mock(SearchOfficesParams::class);
        $offices = OfficeData::getTestData(random_int(2, 5));
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(OfficesResource::class)
            ->callSequence('includeData', 'search', 'all')
            ->methodExpectsArgs('search', [$searchOfficesParamsMock])
            ->willReturn(new PestRoutesCollection($offices->all()))
            ->mock();

        $subject = new PestRoutesOfficesDataProcessor($pestRoutesClientMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchOfficesParamsMock);

        $this->assertEquals($offices, $result);
    }
}
