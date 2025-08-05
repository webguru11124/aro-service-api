<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonTimeZone;
use Tests\Tools\TestValue;

class OfficeFactory extends AbstractFactory
{
    public function single($overrides = []): Office
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(3);
        $name = $overrides['name'] ?? $this->faker->company();
        $region = $overrides['region'] ?? $this->faker->randomElement(TestValue::OFFICE_REGIONS);
        $timeZone = CarbonTimeZone::create($overrides['timezone'] ?? TestValue::TIME_ZONE);
        $address = $overrides['address'] ?? new Address(
            $this->faker->streetAddress(),
            $this->faker->city(),
            $this->faker->regexify('[A-Z]{2}'),
            $this->faker->postcode(),
        );
        $location = $overrides['location'] ?? new Coordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );

        return new Office(
            id: $id,
            name: $name,
            region: $region,
            address: $address,
            timezone: $timeZone,
            location: $location,
        );
    }
}
