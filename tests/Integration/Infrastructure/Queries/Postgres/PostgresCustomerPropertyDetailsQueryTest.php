<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Queries\Postgres;

use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Infrastructure\Queries\Postgres\PostgresCustomerPropertyDetailsQuery;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\RouteOptimization\Entities\Customer;
use Tests\Tools\DatabaseSeeders\CustomerPropertyDetailsSeeder;
use Tests\Tools\TestValue;

class PostgresCustomerPropertyDetailsQueryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresCustomerPropertyDetailsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query = new PostgresCustomerPropertyDetailsQuery();
        $this->seed([
            CustomerPropertyDetailsSeeder::class,
        ]);
    }

    /** @test */
    public function it_retrieves_customer_property_details_correctly()
    {
        $customerIds = [TestValue::CUSTOMER_PROPERTY_DETAILS_ID, 2345];
        $customers = $this->query->get($customerIds);

        $this->assertInstanceOf(Collection::class, $customers);
        $this->assertCount(2, $customers);

        $customer = $customers->first();
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals(TestValue::CUSTOMER_PROPERTY_DETAILS_ID, $customer->getId());
        $this->assertInstanceOf(PropertyDetails::class, $customer->getPropertyDetails());
        $this->assertEquals(1000.0, $customer->getPropertyDetails()->getLandSqFt());
        $this->assertEquals(800.0, $customer->getPropertyDetails()->getBuildingSqFt());
        $this->assertEquals(500.0, $customer->getPropertyDetails()->getLivingSqFt());
    }
}
