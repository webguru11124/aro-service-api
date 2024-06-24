<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SharedKernel\Entities;

use App\Domain\SharedKernel\Entities\Customer;
use App\Domain\SharedKernel\ValueObjects\Address;
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
            id: TestValue::CUSTOMER_ID,
            name: 'Test Name',
            location: new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
            address: new Address(
                address: 'Test Address',
                city: 'Test City',
                state: 'NY',
                zip: '12345',
            ),
            phone: TestValue::CUSTOMER_PHONE,
        );

        $this->assertEquals(TestValue::CUSTOMER_ID, $customer->getId());
        $this->assertEquals('Test Name', $customer->getName());
        $this->assertEquals(TestValue::MIN_LATITUDE, $customer->getLocation()->getLatitude());
        $this->assertEquals(TestValue::MIN_LONGITUDE, $customer->getLocation()->getLongitude());
        $this->assertEquals('Test Address', $customer->getAddress()->getAddress());
        $this->assertEquals('Test City', $customer->getAddress()->getCity());
        $this->assertEquals('NY', $customer->getAddress()->getState());
        $this->assertEquals('12345', $customer->getAddress()->getZip());
        $this->assertEquals(TestValue::CUSTOMER_PHONE, $customer->getPhone());
    }
}
