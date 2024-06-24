<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Scheduling;

use App\Domain\Scheduling\Entities\Customer;
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
            $overrides['id'] ?? $this->faker->randomNumber(),
            $overrides['name'] ?? $this->faker->name(),
            $location,
            $overrides['email'] ?? $this->faker->email(),
            array_key_exists('preferredTechId', $overrides) ? $overrides['preferredTechId'] : $this->faker->randomNumber(3),
        );
    }
}
