<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Postgres;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\Contracts\Queries\CustomerPropertyDetailsQuery;

class PostgresCustomerPropertyDetailsQuery implements CustomerPropertyDetailsQuery
{
    /**
     * Get customer property details by customer IDs.
     *
     * @param array<int> $customerIds
     *
     * @return Collection<Customer>
     */
    public function get($customerIds): Collection
    {
        $results = DB::table(PostgresDBInfo::CUSTOMER_PROPERTY_DETAILS_TABLE)
            ->whereIn('customer_id', $customerIds)
            ->whereNull('deleted_at')
            ->get();

        return $results->map(function ($item) {
            $propertyDetails = new PropertyDetails(
                landSqFt: (float) $item->land_sqft,
                buildingSqFt: (float) $item->building_sqft,
                livingSqFt: (float) $item->living_sqft,
            );

            return new Customer(
                id: (int) $item->customer_id,
                propertyDetails: $propertyDetails,
            );
        });
    }
}
