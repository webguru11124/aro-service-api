<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Exceptions\RouteTemplatesNotFoundException;
use App\Infrastructure\Queries\PestRoutes\PestRoutesGetRouteTemplateQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesRouteTemplatesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RouteTemplatesDataProcessor;
use Illuminate\Support\Collection;
use Mockery;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\Params\SearchRouteTemplatesParams;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplate;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class PestRoutesGetRouteTemplateQueryTest extends TestCase
{
    private PestRoutesGetRouteTemplateQuery $query;
    private RouteTemplatesDataProcessor|MockInterface $dataProcessorMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProcessorMock = Mockery::mock(PestRoutesRouteTemplatesDataProcessorCacheWrapper::class);
        $this->query = new PestRoutesGetRouteTemplateQuery($this->dataProcessorMock);
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_gets_applicable_route_template(Collection $routeTemplates, string|null $expectedException, int|null $expectedResult): void
    {
        $office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $this->assertNotNull($office);
        $this->dataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->with(
                $office->getId(),
                Mockery::type(SearchRouteTemplatesParams::class)
            )
            ->andReturn($routeTemplates);

        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $result = $this->query->get($office);

        if (!$expectedException) {
            $this->assertEquals($expectedResult, $result);
        }
    }

    public static function dataProvider()
    {
        $template1 = new RouteTemplate(
            id: 1,
            templateName: 'Regular Routes',
            officeId: TestValue::OFFICE_ID,
            isDefaultForOffice: true,
            isVisible: true
        );
        $template2 = new RouteTemplate(
            id: 2,
            templateName: 'Special Routes',
            officeId: TestValue::OFFICE_ID,
            isDefaultForOffice: false,
            isVisible: true
        );

        return [
            'no templates found' => [
                'routeTemplates' => new Collection(),
                'expectedException' => RouteTemplatesNotFoundException::class,
                'expectedResult' => null,
            ],
            'no applicable template found' => [
                'routeTemplates' => new Collection([$template2]),
                'expectedException' => RouteTemplatesNotFoundException::class,
                'expectedResult' => null,
            ],
            'applicable template found' => [
                'routeTemplates' => new Collection([$template1, $template2]),
                'expectedException' => null,
                'expectedResult' => 1,
            ],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
