<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\RouteType;
use Tests\TestCase;

class RouteTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_type_correctly(): void
    {
        $routeTypeRegular = RouteType::fromString('Regular Route');
        $routeTypeExtended = RouteType::fromString('Extended');
        $routeTypeShort = RouteType::fromString('Short');

        $this->assertEquals(RouteType::REGULAR_ROUTE, $routeTypeRegular);
        $this->assertEquals(RouteType::EXTENDED_ROUTE, $routeTypeExtended);
        $this->assertEquals(RouteType::SHORT_ROUTE, $routeTypeShort);
    }
}
