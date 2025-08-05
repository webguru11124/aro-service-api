<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Infrastructure\Repositories\Postgres\PostgresCustomerRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Tools\TestValue;

class PostgresCustomerRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private const TABLE_NAME = 'field_operations.customer_property_details';

    private PostgresCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresCustomerRepository();
    }

    /**
     * @test
     */
    public function it_saves_and_retrieves_customer_with_property_details(): void
    {
        $customerId = TestValue::CUSTOMER_PROPERTY_DETAILS_ID;
        $propertyDetails = new PropertyDetails(landSqFt: 1000, buildingSqFt: 500, livingSqFt: 300);
        $customer = new Customer(id: $customerId, propertyDetails: $propertyDetails);

        $this->repository->save($customer);

        $retrievedCustomer = $this->repository->find($customerId);
        $retrievedDetails = $retrievedCustomer->getPropertyDetails();

        $this->assertInstanceOf(Customer::class, $retrievedCustomer);
        $this->assertEquals($customerId, $retrievedCustomer->getId());
        $this->assertEquals($propertyDetails->getLandSqFt(), $retrievedDetails->getLandSqFt());
        $this->assertEquals($propertyDetails->getBuildingSqFt(), $retrievedDetails->getBuildingSqFt());
        $this->assertEquals($propertyDetails->getLivingSqFt(), $retrievedDetails->getLivingSqFt());
    }

    /**
     * @test
     */
    public function it_soft_deletes_existing_property_details_before_saving_new_one(): void
    {
        $customerId = TestValue::CUSTOMER_PROPERTY_DETAILS_ID;
        $originalPropertyDetails = new PropertyDetails(landSqFt: 900, buildingSqFt: 400, livingSqFt: 250);
        $updatedPropertyDetails = new PropertyDetails(landSqFt: 1100, buildingSqFt: 600, livingSqFt: 350);

        $originalCustomer = new Customer(id: $customerId, propertyDetails: $originalPropertyDetails);
        $this->repository->save($originalCustomer);

        $updatedCustomer = new Customer(id: $customerId, propertyDetails: $updatedPropertyDetails);
        $this->repository->save($updatedCustomer);

        $retrievedCustomer = $this->repository->find($customerId);
        $retrievedDetails = $retrievedCustomer->getPropertyDetails();

        $this->assertEquals($updatedPropertyDetails->getLandSqFt(), $retrievedDetails->getLandSqFt());
        $this->assertEquals($updatedPropertyDetails->getBuildingSqFt(), $retrievedDetails->getBuildingSqFt());
        $this->assertEquals($updatedPropertyDetails->getLivingSqFt(), $retrievedDetails->getLivingSqFt());
    }
}
