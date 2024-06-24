<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Tracking;

use App\Domain\SharedKernel\Entities\Customer;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class CustomerFactory extends AbstractFactory
{
    public function single($overrides = []): Customer
    {
        $location = $overrides['location'] ?? new Coordinate(
            $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
            $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
        );

        return new Customer(
            id: $overrides['id'] ?? $this->faker->randomNumber(),
            name: $overrides['name'] ?? $this->faker->name(),
            location: $location,
            address: new Address(
                address: $this->faker->streetAddress,
                city: $this->faker->city,
                state: $this->faker->state,
                zip: $this->faker->postcode,
            ),
            phone: $this->faker->phoneNumber,
        );
    }
}
