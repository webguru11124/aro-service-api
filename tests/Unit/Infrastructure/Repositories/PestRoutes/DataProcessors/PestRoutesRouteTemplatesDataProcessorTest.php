<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRouteTemplatesDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\Params\SearchRouteTemplatesParams;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplatesResource;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\RouteTemplateData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesRouteTemplatesDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    /**
     * @test
     */
    public function it_extracts_routes_templates(): void
    {
        $searchParamsMock = new SearchRouteTemplatesParams(
            officeIds: [TestValue::OFFICE_ID]
        );

        $routeTemplates = RouteTemplateData::getTestData(3);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(RouteTemplatesResource::class)
            ->callSequence('routeTemplates', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchRouteTemplatesParams $params) {
                $array = $params->toArray();

                return $array['officeIDs'] === [TestValue::OFFICE_ID];
            })
            ->willReturn(new PestRoutesCollection($routeTemplates->all()))
            ->mock();

        $processor = new PestRoutesRouteTemplatesDataProcessor($pestRoutesClientMock);

        $result = $processor->extract(TestValue::OFFICE_ID, $searchParamsMock);

        $this->assertEquals($routeTemplates, $result);
    }
}
