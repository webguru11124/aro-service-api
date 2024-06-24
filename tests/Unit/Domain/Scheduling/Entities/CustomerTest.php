<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\Customer;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CustomerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $customer = new Customer(
            TestValue::CUSTOMER_ID,
            'Test Name',
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            $this->faker->email(),
            $this->faker->randomNumber(3),
        );

        $this->assertEquals(TestValue::CUSTOMER_ID, $customer->getId());
        $this->assertEquals('Test Name', $customer->getName());
        $this->assertEquals(TestValue::MIN_LATITUDE, $customer->getLocation()->getLatitude());
        $this->assertEquals(TestValue::MIN_LONGITUDE, $customer->getLocation()->getLongitude());
    }

    /**
     * @test
     */
    public function it_can_reset_preferred_tech_id(): void
    {
        $customer = new Customer(
            TestValue::CUSTOMER_ID,
            'Test Name',
            new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            $this->faker->email(),
            $this->faker->randomNumber(3),
        );

        $customer->resetPreferredTechId();

        $this->assertNull($customer->getPreferredTechId());
    }
}
