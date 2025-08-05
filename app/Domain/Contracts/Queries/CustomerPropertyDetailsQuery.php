<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use Illuminate\Support\Collection;
use App\Domain\RouteOptimization\Entities\Customer;

interface CustomerPropertyDetailsQuery
{
    /**
     * Get customer property details by customer IDs.
     *
     * @param array<int> $customerIds
     *
     * @return Collection<Customer>
     */
    public function get($customerIds): Collection;
}
