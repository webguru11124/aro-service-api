<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\PestRoutesGetRoutesByOfficeAndDateQuery;
use App\Infrastructure\Repositories\PestRoutes\Translators\RouteCreation\PestRoutesRouteTranslator;
use Illuminate\Support\Collection;
use Mockery;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\TestValue;
use Tests\Traits\RouteResolverTraitTest;

class PestRoutesGetRoutesByOfficeAndDateQueryTest extends TestCase
{
    use RouteResolverTraitTest;

    private PestRoutesRouteTranslator|MockInterface $translatorMock;
    private PestRoutesGetRoutesByOfficeAndDateQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock = Mockery::mock(PestRoutesRouteTranslator::class);
        $this->query = new PestRoutesGetRoutesByOfficeAndDateQuery($this->translatorMock, $this->routesDataProcessorMock);
    }

    /**
     * @test
     */
    public function it_gets_route(): void
    {
        $office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $date = Carbon::tomorrow();

        $routes = RouteData::getTestData(3);
        $this->setMockGetRegularRoutes($office, $date, $routes);
        $this->translatorMock
            ->shouldReceive('toDomain')
            ->times(3);

        $result = $this->query->get($office, $date);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    /**
     * @test
     *
     * @dataProvider exceptionDataProvider
     */
    public function it_handles_no_routes_found(Collection $routes): void
    {
        $office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $date = Carbon::tomorrow();

        $this->setMockGetRegularRoutes($office, $date, $routes);
        $this->translatorMock
            ->shouldReceive('toDomain')
            ->never();

        $result = $this->query->get($office, $date);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
