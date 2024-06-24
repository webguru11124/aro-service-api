<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Entities;

use App\Infrastructure\Services\PestRoutes\Entities\SpotFactory;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class SpotFactoryTest extends TestCase
{
    private SpotFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SpotFactory();
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_makes_proper_spot(PestRoutesSpot $spot, PestRoutesRoute $route, SpotType $expectedType): void
    {
        $result = $this->factory->makeSpot($spot, $route);

        $this->assertEquals($expectedType, $result->getType());
    }

    public static function dataProvider(): iterable
    {
        yield [
            'spot' => SpotData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => '\ARO {"from": [-97.7791, 30.2999], "to": [-97.7612, 30.2981], "skills": ["INI", "TX"], "time": [8, 12]}',
            ])->first(),
            'route' => RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'title' => 'Regular Routes',
            ])->first(),
            'expectedType' => SpotType::ARO_BLOCKED,
        ];

        yield [
            'spot' => SpotData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'blockReason' => '',
            ])->first(),
            'route' => RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'title' => 'Bucket Routes',
            ])->first(),
            'expectedType' => SpotType::BUCKET,
        ];

        yield [
            'spot' => SpotData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'blockReason' => '',
            ])->first(),
            'route' => RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'title' => 'Regular Routes',
            ])->first(),
            'expectedType' => SpotType::REGULAR,
        ];

        yield [
            'spot' => SpotData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'blockReason' => '',
                'previousLat' => null,
                'previousLng' => null,
                'nextLat' => null,
                'nextLng' => null,
            ])->first(),
            'route' => RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'title' => 'Bucket Routes',
            ])->first(),
            'expectedType' => SpotType::BUCKET,
        ];

        yield [
            'spot' => SpotData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'spotCapacity' => '29',
                'blockReason' => '\ARO {"from": [-97.7791, 30.2999], "to": [-97.7612, 30.2981], "skills": ["INI", "TX"], "time": [8, 12]}',
            ])->first(),
            'route' => RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'title' => 'Regular Routes',
            ])->first(),
            'expectedType' => SpotType::REGULAR,
        ];
    }
}
