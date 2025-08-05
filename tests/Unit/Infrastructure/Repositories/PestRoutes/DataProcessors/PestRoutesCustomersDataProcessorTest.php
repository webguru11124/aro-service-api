<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use Aptive\PestRoutesSDK\Resources\Customers\Params\UpdateCustomersParams;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Customers\CustomersResource;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesCustomersDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    /**
     * @test
     */
    public function it_extracts_customers(): void
    {
        $searchCustomersParamsMock = \Mockery::mock(SearchCustomersParams::class);
        $customers = CustomerData::getTestData(random_int(2, 5));
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(CustomersResource::class)
            ->callSequence('customers', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', [$searchCustomersParamsMock])
            ->willReturn(new PestRoutesCollection($customers->all()))
            ->mock();

        $subject = new PestRoutesCustomersDataProcessor($pestRoutesClientMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchCustomersParamsMock);

        $this->assertEquals($customers, $result);
    }

    /**
     * @test
     */
    public function it_updates_customers(): void
    {
        $updateCustomersParamsMock = \Mockery::mock(UpdateCustomersParams::class);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(CustomersResource::class)
            ->callSequence('customers', 'update')
            ->methodExpectsArgs('update', [$updateCustomersParamsMock])
            ->willReturn(true)
            ->mock();

        $subject = new PestRoutesCustomersDataProcessor($clientMock);

        $result = $subject->update(TestValue::OFFICE_ID, $updateCustomersParamsMock);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_resets_preferred_tech(): void
    {
        $customerId = random_int(1, 100);
        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(CustomersResource::class)
            ->callSequence('customers', 'update')
            ->willReturn(true)
            ->mock();

        $subject = new PestRoutesCustomersDataProcessor($clientMock);

        $result = $subject->resetPreferredTech(TestValue::OFFICE_ID, $customerId);

        $this->assertTrue($result);
    }
}
