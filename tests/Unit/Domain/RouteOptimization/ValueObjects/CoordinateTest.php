<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CoordinateTest extends TestCase
{
    /**
     * @test
     */
    public function create_coordinate(): void
    {
        $coordinate = new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE);

        $this->assertEquals(TestValue::LATITUDE, $coordinate->getLatitude());
        $this->assertEquals(TestValue::LONGITUDE, $coordinate->getLongitude());
    }
}
