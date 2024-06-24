<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use Tests\TestCase;
use Tests\Tools\TestValue;

class CustomerTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_and_gets_property_details(): void
    {
        $customerId = TestValue::CUSTOMER_PROPERTY_DETAILS_ID;
        $landSqft = 1200.5;
        $buildingSqft = 800.75;
        $livingSqft = 600.25;

        $propertyDetails = new PropertyDetails($landSqft, $buildingSqft, $livingSqft);
        $customer = new Customer($customerId, null);

        $customer->setPropertyDetails($propertyDetails);

        $retrievedPropertyDetails = $customer->getPropertyDetails();

        $this->assertNotNull($retrievedPropertyDetails);
        $this->assertEquals($landSqft, $retrievedPropertyDetails->getLandSqFt());
        $this->assertEquals($buildingSqft, $retrievedPropertyDetails->getBuildingSqFt());
        $this->assertEquals($livingSqft, $retrievedPropertyDetails->getLivingSqFt());
    }

    /**
     * @test
     */
    public function it_allows_null_property_details(): void
    {
        $customerId = TestValue::CUSTOMER_PROPERTY_DETAILS_ID;
        $customer = new Customer($customerId, null);

        $propertyDetails = $customer->getPropertyDetails();

        $this->assertNull($propertyDetails);
    }

    /**
     * @test
     */
    public function it_correctly_returns_customer_id(): void
    {
        $customerId = TestValue::CUSTOMER_PROPERTY_DETAILS_ID;
        $customer = new Customer($customerId, null);

        $this->assertEquals($customerId, $customer->getId());
    }
}
