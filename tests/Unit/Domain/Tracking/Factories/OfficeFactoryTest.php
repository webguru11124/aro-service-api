<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\Tracking\Factories\OfficeFactory;
use Tests\TestCase;

class OfficeFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_office_from_array(): void
    {
        $office = (new OfficeFactory())->create([
            'officeID' => 1,
            'officeName' => 'Office 1',
            'region' => 'Region 1',
            'address' => 'Address 1',
            'city' => 'City 1',
            'state' => 'State 1',
            'timeZone' => 'UTC',
            'location' => [1.1, 2.1],
        ]);

        $this->assertEquals(1, $office->getId());
        $this->assertEquals('Office 1', $office->getName());
        $this->assertEquals('Region 1', $office->getRegion());
        $this->assertEquals('Address 1', $office->getAddress());
        $this->assertEquals('City 1', $office->getCity());
        $this->assertEquals('State 1', $office->getState());
        $this->assertEquals('UTC', $office->getTimezone());
        $this->assertEquals(1.1, $office->getLocation()->getLatitude());
        $this->assertEquals(2.1, $office->getLocation()->getLongitude());
    }
}
