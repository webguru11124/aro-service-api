<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Tools\PestRoutesData\RouteData;

trait RouteResolverTraitTest
{
    protected PestRoutesRoutesDataProcessor|MockInterface $routesDataProcessorMock;

    public function setupRouteResolverTraitTest(): void
    {
        $this->routesDataProcessorMock = Mockery::mock(PestRoutesRoutesDataProcessor::class);
    }

    protected function setUp(): void
    {
        $this->setupRouteResolverTraitTest();
    }

    protected function setMockGetRegularRoutes(Office $office, CarbonInterface $date, Collection $routes): void
    {
        $params = new SearchRoutesParams(
            officeIds: [$office->getId()],
            dateStart: $date->clone()->startOfDay(),
            dateEnd: $date->clone()->endOfDay(),
        );

        $expectedParamsArray = $params->toArray();

        $this->routesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->with(
                $office->getId(),
                Mockery::on(fn ($param) => $param instanceof SearchRoutesParams && $param->toArray() == $expectedParamsArray),
            )
            ->andReturn($routes);
    }

    public static function exceptionDataProvider(): array
    {
        return [
            'GroupTitle is not regular' => [
                'routes' => RouteData::getTestData(
                    3,
                    ['groupTitle' => 'Unknown'],
                    ['groupTitle' => 'Unknown'],
                    ['groupTitle' => 'Unknown'],
                ),
            ],
            'RouteType is unknown' => [
                'routes' => collect(RouteData::getTestData(
                    3,
                    ['title' => 'Unknown'],
                    ['title' => 'Unknown'],
                    ['title' => 'Unknown'],
                )),
            ],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
