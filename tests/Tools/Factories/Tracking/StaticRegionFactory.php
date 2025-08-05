<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\Entities\Region;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class StaticRegionFactory extends AbstractFactory
{
    protected function single($overrides = []): Region
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(3);
        $name = $overrides['name'] ?? $this->faker->randomElement(TestValue::OFFICE_REGIONS);
        $offices = $overrides['offices'] ?? collect(OfficeFactory::many(3, ['region' => $name]));

        $region = new Region(
            id: $id,
            name: $name,
        );

        $offices->each(fn (Office $office) => $region->addOffice($office));

        return $region;
    }
}
