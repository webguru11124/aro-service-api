<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Routes\RoutesResource;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\UpdateRoutesParams;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesRoutesDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private const DATE = '2022-02-24';
    private const TEMPLATE_ID = 123;
    private const ASSIGNED_TECH_ID = 235443;

    /**
     * @test
     */
    public function it_extracts_routes(): void
    {
        $searchRoutesParamsMock = \Mockery::mock(SearchRoutesParams::class);
        $routes = new PestRoutesCollection(RouteData::getTestData(random_int(2, 5))->all());

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(RoutesResource::class)
            ->callSequence('routes', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', [$searchRoutesParamsMock])
            ->willReturn($routes)
            ->mock();

        $subject = new PestRoutesRoutesDataProcessor($clientMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchRoutesParamsMock);

        $this->assertEquals($routes->items, $result->all());
    }

    /**
     * @test
     */
    public function it_creates_a_route(): void
    {
        $createRoutesParamsMock = \Mockery::mock(CreateRoutesParams::class);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(RoutesResource::class)
            ->callSequence('routes', 'create')
            ->methodExpectsArgs('create', [$createRoutesParamsMock])
            ->willReturn(TestValue::ROUTE_ID)
            ->mock();

        $subject = new PestRoutesRoutesDataProcessor($clientMock);

        $result = $subject->create(TestValue::OFFICE_ID, $createRoutesParamsMock);

        $this->assertEquals(TestValue::ROUTE_ID, $result);
    }

    /**
     * @test
     */
    public function it_updates_a_route(): void
    {
        $updateRoutesParamsMock = \Mockery::mock(UpdateRoutesParams::class);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(RoutesResource::class)
            ->callSequence('routes', 'update')
            ->methodExpectsArgs('update', [$updateRoutesParamsMock])
            ->willReturn(TestValue::ROUTE_ID)
            ->mock();

        $subject = new PestRoutesRoutesDataProcessor($clientMock);

        $result = $subject->update(TestValue::OFFICE_ID, $updateRoutesParamsMock);

        $this->assertEquals(TestValue::ROUTE_ID, $result);
    }

    /**
     * @test
     */
    public function it_deletes_route(): void
    {
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(RoutesResource::class)
            ->callSequence('routes', 'delete')
            ->methodExpectsArgs('delete', [TestValue::ROUTE_ID])
            ->willReturn(true)
            ->mock();

        $subject = new PestRoutesRoutesDataProcessor($clientMock);

        $result = $subject->delete(TestValue::OFFICE_ID, TestValue::ROUTE_ID);

        $this->assertTrue($result);
    }
}
