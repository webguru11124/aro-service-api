<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocation;
use Carbon\Carbon;
use Tests\TestCase;

class VehicleLocationTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_object_properly(): void
    {
        Carbon::setTestNow(Carbon::now());

        $expectedObject = new VehicleLocation(
            vehicleId: 1,
            lastSeenAt: Carbon::now(),
            speed: 10.0,
            location: new Coordinate(1.0, 1.0),
        );

        $testObject = VehicleLocation::fromApiObject((object) [
            'id' => 1,
            'current_location' => (object) [
                'located_at' => Carbon::now(),
                'speed' => 10.0,
                'lat' => 1.0,
                'lon' => 1.0,
            ],
        ]);

        $this->assertEquals($expectedObject, $testObject);
    }
}
