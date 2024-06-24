<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\RouteOptimization\Entities\Customer;
use App\Infrastructure\Exceptions\CustomerNotFoundException;

interface CustomerRepository
{
    /**
     * Finds a Customer entity by its ID.
     *
     * @param int $customerId The unique identifier of the customer.
     *
     * @return Customer The found Customer entity.
     * @throws CustomerNotFoundException If the customer is not found.
     */
    public function find(int $customerId): Customer;

    /**
     * Saves a Customer entity, including its PropertyDetails.
     *
     * If the customer already exists, this method should handle updating the existing record appropriately,
     * including soft deleting the current PropertyDetails before saving the new ones.
     *
     * @param Customer $customer The Customer entity to save.
     *
     * @return void
     */
    public function save(Customer $customer): void;
}
