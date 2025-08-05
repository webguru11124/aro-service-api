<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\Tracking\Factories\RegionFactory;
use Tests\TestCase;
use Tests\Tools\TestValue;

class RegionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_region_from_array(): void
    {
        $region = (new RegionFactory())->create([
            'id' => TestValue::REGION_ID,
            'name' => TestValue::REGION_NAME,
        ]);

        $this->assertEquals(TestValue::REGION_ID, $region->getId());
        $this->assertEquals(TestValue::REGION_NAME, $region->getName());
    }
}
