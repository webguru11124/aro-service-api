<?php

declare(strict_types=1);

namespace App\Application\Commands\UpdatePropertyDetails;

use App\Domain\Contracts\Repositories\CustomerRepository;
use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Infrastructure\Exceptions\CustomerNotFoundException;

class UpdatePropertyDetailsHandler
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    public function handle(UpdatePropertyDetailsCommand $command): void
    {
        try {
            $customer = $this->customerRepository->find($command->customerId);
        } catch (CustomerNotFoundException $exception) {
            $customer = new Customer($command->customerId);
        }

        $customer->setPropertyDetails(new PropertyDetails(
            $command->landSqft,
            $command->buildingSqft,
            $command->livingSqft
        ));

        $this->customerRepository->save($customer);
    }
}
