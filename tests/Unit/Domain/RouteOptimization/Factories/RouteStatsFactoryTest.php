<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\Factories\RouteStatsFactory;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use PHPUnit\Framework\TestCase;
use Tests\Tools\TestValue;
use Tests\Traits\RouteStatsData;

class RouteStatsFactoryTest extends TestCase
{
    use RouteStatsData;

    private RouteStatsFactory $routeStatsFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeStatsFactory = new RouteStatsFactory();
    }

    /**
     * @test
     */
    public function it_creates_a_route_stats(): void
    {
        $data = json_decode(self::ROUTE_STATS[TestValue::ROUTE_ID], true);

        $routeStats = $this->routeStatsFactory->create($data);

        $this->assertInstanceOf(RouteStats::class, $routeStats);
        $this->assertEquals($data, $routeStats->toArray());
    }
}
