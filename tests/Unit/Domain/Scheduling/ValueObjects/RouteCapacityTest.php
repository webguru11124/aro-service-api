<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\Scheduling\ValueObjects\RouteCapacity;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RouteCapacityTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider capacityValuesProvider
     */
    public function it_returns_capacity_value_for_regular_route(
        RouteType $routeType,
        int $actualCapacity,
        Collection $eventDurations,
        int $expectedValue
    ): void {
        $routeCapacity = new RouteCapacity(
            $routeType,
            $actualCapacity,
            $eventDurations
        );

        $this->assertEquals($expectedValue, $routeCapacity->getValue());
    }

    public static function capacityValuesProvider(): iterable
    {
        yield [RouteType::REGULAR_ROUTE, 4, collect([Duration::fromMinutes(180)]), 0];
        yield [RouteType::REGULAR_ROUTE, 5, collect(), 5];
        yield [RouteType::REGULAR_ROUTE, 22, collect(), 16];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(15)]), 15];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(30)]), 15];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(31)]), 14];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(15), Duration::fromMinutes(15)]), 14];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(15), Duration::fromMinutes(15), Duration::fromMinutes(15)]), 13];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(45), Duration::fromMinutes(15)]), 13];
        yield [RouteType::REGULAR_ROUTE, 22, collect([Duration::fromMinutes(45), Duration::fromMinutes(15), Duration::fromMinutes(15)]), 12];
        yield [RouteType::SHORT_ROUTE, 20, collect(), 15];
        yield [RouteType::SHORT_ROUTE, 20, collect([Duration::fromMinutes(15)]), 14];
        yield [RouteType::EXTENDED_ROUTE, 24, collect(), 18];
        yield [RouteType::EXTENDED_ROUTE, 24, collect([Duration::fromMinutes(45)]), 16];
    }
}
