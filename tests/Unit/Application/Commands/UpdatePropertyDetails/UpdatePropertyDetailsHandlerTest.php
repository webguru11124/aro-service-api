<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Commands\UpdatePropertyDetails;

use App\Application\Commands\UpdatePropertyDetails\UpdatePropertyDetailsCommand;
use App\Application\Commands\UpdatePropertyDetails\UpdatePropertyDetailsHandler;
use App\Domain\Contracts\Repositories\CustomerRepository;
use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Infrastructure\Exceptions\CustomerNotFoundException;
use Mockery;
use Tests\TestCase;

class UpdatePropertyDetailsHandlerTest extends TestCase
{
    private CustomerRepository|Mockery\MockInterface $customerRepository;
    private UpdatePropertyDetailsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = Mockery::mock(CustomerRepository::class);
        $this->handler = new UpdatePropertyDetailsHandler($this->customerRepository);
    }

    /**
     * @test
     */
    public function it_creates_new_customer_if_not_exists_and_updates_property_details(): void
    {
        $command = new UpdatePropertyDetailsCommand(
            customerId: 1,
            landSqft: 1000.0,
            buildingSqft: 500.0,
            livingSqft: 300.0
        );

        $this->customerRepository->shouldReceive('find')
            ->once()
            ->with($command->customerId)
            ->andThrow(new CustomerNotFoundException());

        $this->customerRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Customer $customer) use ($command) {
                return $customer->getPropertyDetails()->getLandSqFt() === $command->landSqft
                    && $customer->getPropertyDetails()->getBuildingSqFt() === $command->buildingSqft
                    && $customer->getPropertyDetails()->getLivingSqFt() === $command->livingSqft;
            });

        $this->handler->handle($command);
    }

    /**
     * @test
     */
    public function it_updates_existing_customer_property_details(): void
    {
        $command = new UpdatePropertyDetailsCommand(
            customerId: 2,
            landSqft: 2000.0,
            buildingSqft: 1000.0,
            livingSqft: 750.0
        );

        $existingCustomer = new Customer($command->customerId, new PropertyDetails(1500.0, 750.0, 500.0));

        $this->customerRepository->shouldReceive('find')
            ->once()
            ->with($command->customerId)
            ->andReturn($existingCustomer);

        $this->customerRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (Customer $customer) use ($command) {
                $propertyDetails = $customer->getPropertyDetails();

                return $propertyDetails->getLandSqFt() === $command->landSqft
                    && $propertyDetails->getBuildingSqFt() === $command->buildingSqft
                    && $propertyDetails->getLivingSqFt() === $command->livingSqft;
            });

        $this->handler->handle($command);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->customerRepository,
            $this->handler
        );
    }
}
