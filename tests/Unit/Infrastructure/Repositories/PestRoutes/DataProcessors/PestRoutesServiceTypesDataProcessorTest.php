<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesServiceTypesDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceTypesResource;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesServiceTypesDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private const SERVICE_TYPES_IDS = [1, 39];
    private const MUTUAL_SERVICE_TYPE_OFFICE_ID = -1;

    /**
     * @test
     */
    public function it_extracts_service_types(): void
    {
        $searchServiceTypesParamsMock = new SearchServiceTypesParams(
            ids: self::SERVICE_TYPES_IDS,
            officeIds: [TestValue::OFFICE_ID]
        );

        $serviceTypes = ServiceTypeData::getTestDataOfTypes(
            ServiceTypeData::PRO,
            ServiceTypeData::RESERVICE,
            ServiceTypeData::MOSQUITO,
        );

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(ServiceTypesResource::class)
            ->callSequence('serviceTypes', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchServiceTypesParams $params) {
                $array = $params->toArray();

                return $array['officeIDs'] === [TestValue::OFFICE_ID, self::MUTUAL_SERVICE_TYPE_OFFICE_ID]
                    && $array['typeIDs'] === self::SERVICE_TYPES_IDS;
            })
            ->willReturn(new PestRoutesCollection($serviceTypes->all()))
            ->mock();

        $subject = new PestRoutesServiceTypesDataProcessor($pestRoutesClientMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchServiceTypesParamsMock);

        $this->assertEquals($serviceTypes, $result);
    }
}
