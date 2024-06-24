<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SharedKernel\Entities;

use App\Domain\SharedKernel\Entities\Region;
use App\Domain\Tracking\Exceptions\OfficeAlreadyAddedException;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class RegionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_region(): void
    {
        $region = new Region(
            TestValue::REGION_ID,
            TestValue::REGION_NAME,
        );

        $region->addOffice(OfficeFactory::make());

        $this->assertEquals(TestValue::REGION_ID, $region->getId());
        $this->assertEquals(TestValue::REGION_NAME, $region->getName());
        $this->assertCount(1, $region->getOffices());
        $this->assertCount(1, $region->getBoundaryPolygon()->getVertexes());
    }

    /**
     * @test
     *
     * ::addOffice
     */
    public function it_throws_exception_when_office_already_added(): void
    {
        $region = new Region(
            TestValue::REGION_ID,
            TestValue::REGION_NAME,
        );

        $this->expectException(OfficeAlreadyAddedException::class);

        $region->addOffice(OfficeFactory::make(['id' => TestValue::OFFICE_ID]));
        $region->addOffice(OfficeFactory::make(['id' => TestValue::OFFICE_ID]));
    }
}
