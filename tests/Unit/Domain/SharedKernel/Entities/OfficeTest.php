<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SharedKernel\Entities;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonTimeZone;
use Tests\TestCase;
use Tests\Tools\TestValue;

class OfficeTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_office(): void
    {
        $office = new Office(
            1,
            'Office 1',
            'Region 1',
            new Address('Address 1', 'City 1', 'State 1', 'Zip 1'),
            new CarbonTimeZone('America/Chicago'),
            new Coordinate(1.0, 2.0),
        );

        $this->assertEquals(1, $office->getId());
        $this->assertEquals('Office 1', $office->getName());
        $this->assertEquals('Region 1', $office->getRegion());
        $this->assertEquals('Address 1', $office->getAddress());
        $this->assertEquals('City 1', $office->getCity());
        $this->assertEquals('State 1', $office->getState());
        $this->assertEquals('America/Chicago', $office->getTimezone());
        $this->assertEquals(1.0, $office->getLocation()->getLatitude());
        $this->assertEquals(2.0, $office->getLocation()->getLongitude());
        $this->assertTrue($office->isLocationInServiceableArea(new Coordinate(1.0001, 2.0001)));
    }

    /**
     * @test
     */
    public function it_returns_true_if_location_in_serviceable_area(): void
    {
        $office = new Office(
            1,
            'Office 1',
            'Region 1',
            new Address('Address 1', 'City 1', 'State 1', 'Zip 1'),
            new CarbonTimeZone('America/Chicago'),
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );

        $this->assertTrue($office->isLocationInServiceableArea(
            new Coordinate(TestValue::MIN_LATITUDE + 0.0001, TestValue::MIN_LONGITUDE + 0.0001),
        ));
    }

    /**
     * @test
     */
    public function it_returns_false_if_location_not_in_serviceable_area(): void
    {
        $office = new Office(
            1,
            'Office 1',
            'Region 1',
            new Address('Address 1', 'City 1', 'State 1', 'Zip 1'),
            new CarbonTimeZone('America/Chicago'),
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        );

        $this->assertFalse($office->isLocationInServiceableArea(
            new Coordinate(TestValue::MAX_LATITUDE + 1, TestValue::MAX_LONGITUDE + 1)
        ));
    }
}
