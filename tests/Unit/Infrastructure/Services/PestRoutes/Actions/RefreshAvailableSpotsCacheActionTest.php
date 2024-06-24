<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Actions;

use App\Infrastructure\Queries\PestRoutes\Params\RoutesCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\Params\SpotsCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\PestRoutesRoutesCachedQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesSpotsCachedQuery;
use App\Infrastructure\Services\PestRoutes\Actions\RefreshAvailableSpotsCacheAction;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class RefreshAvailableSpotsCacheActionTest extends TestCase
{
    private const string START_DATE = '2024-01-01';
    private const string END_DATE = '2024-01-05';
    private const int TTL = 300;

    private RefreshAvailableSpotsCacheAction $action;

    private PestRoutesSpotsCachedQuery|MockInterface $mockSpotsCachedQuery;
    private PestRoutesRoutesCachedQuery|MockInterface $mockRoutesCachedQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSpotsCachedQuery = Mockery::mock(PestRoutesSpotsCachedQuery::class);
        $this->mockRoutesCachedQuery = Mockery::mock(PestRoutesRoutesCachedQuery::class);

        $this->action = new RefreshAvailableSpotsCacheAction(
            $this->mockSpotsCachedQuery,
            $this->mockRoutesCachedQuery,
        );
    }

    /**
     * @test
     */
    public function it_returns_a_bunch_of_routes(): void
    {
        $pestRoutesRoutes = RouteData::getTestData(2);
        $pestRoutesSpots = SpotData::getTestData(4);

        $this->mockRoutesCachedQuery
            ->shouldReceive('cached')
            ->with(self::TTL, true)
            ->once()
            ->andReturnSelf();
        $this->mockRoutesCachedQuery
            ->shouldReceive('get')
            ->withArgs(function (RoutesCachedQueryParams $params) {
                return $params->officeId === TestValue::OFFICE_ID
                    && $params->startDate->toDateString() === self::START_DATE
                    && $params->endDate->toDateString() === self::END_DATE;
            })
            ->once()
            ->andReturn($pestRoutesRoutes);

        $this->mockSpotsCachedQuery
            ->shouldReceive('cached')
            ->with(self::TTL, true)
            ->once()
            ->andReturnSelf();
        $this->mockSpotsCachedQuery
            ->shouldReceive('get')
            ->once()
            ->withArgs(function (SpotsCachedQueryParams $params) {
                return $params->officeId === TestValue::OFFICE_ID
                    && $params->startDate->toDateString() === self::START_DATE
                    && $params->endDate->toDateString() === self::END_DATE
                    && $params->apiCanSchedule === true;
            })
            ->andReturn($pestRoutesSpots);

        $this->action->execute(
            officeId: TestValue::OFFICE_ID,
            startDate: Carbon::parse(self::START_DATE, TestValue::TIME_ZONE),
            endDate: Carbon::parse(self::END_DATE, TestValue::TIME_ZONE),
            ttl: self::TTL
        );
    }
}
