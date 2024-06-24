<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\Tracking\ValueObjects\RouteTrackingData;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\TestValue;

class RouteTrackingDataTest extends TestCase
{
    /**
     * @test
     */
    public function it_formats_object_into_array(): void
    {
        Carbon::setTestNow(Carbon::now());

        $object = new RouteTrackingData(
            TestValue::WORKDAY_ID,
            new Coordinate(1.0, 2.0),
            Carbon::now(),
            new Coordinate(3.0, 4.0),
            Carbon::now(),
            5.0
        );

        $expectedArray = [
            'driver_location' => [
                'lat' => 1.0,
                'lng' => 2.0,
                'timestamp' => Carbon::now()->toISOString(),
            ],
            'vehicle_location' => [
                'lat' => 3.0,
                'lng' => 4.0,
                'timestamp' => Carbon::now()->toISOString(),
            ],
            'vehicle_speed' => 5.0,
        ];

        $this->assertEquals($expectedArray, $object->toArray());
    }
}
