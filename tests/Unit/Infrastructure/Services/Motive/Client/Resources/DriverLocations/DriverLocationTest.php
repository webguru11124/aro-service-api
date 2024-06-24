<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\DriverLocations;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocation;
use Carbon\Carbon;
use Tests\TestCase;

class DriverLocationTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_object_properly(): void
    {
        Carbon::setTestNow(Carbon::now());

        $expectedObject = new DriverLocation(
            driverId: 1,
            vehicleId: 2,
            lastSeenAt: Carbon::now(),
            location: new Coordinate(1.0, 1.0),
        );

        $testObject = DriverLocation::fromApiObject((object) [
            'id' => 1,
            'current_location' => (object) [
                'located_at' => Carbon::now(),
                'lat' => 1.0,
                'lon' => 1.0,
            ],
            'current_vehicle' => (object) [
                'id' => 2,
            ],
        ]);

        $this->assertEquals($expectedObject, $testObject);
    }
}
