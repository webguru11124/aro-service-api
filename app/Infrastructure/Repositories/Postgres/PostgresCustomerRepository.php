<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\Contracts\Repositories\CustomerRepository;
use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Facades\DB;
use App\Infrastructure\Exceptions\CustomerNotFoundException;

class PostgresCustomerRepository extends AbstractPostgresRepository implements CustomerRepository
{
    protected function getTableName(): string
    {
        return PostgresDBInfo::CUSTOMER_PROPERTY_DETAILS_TABLE;
    }

    /**
     * Finds a Customer entity by its ID.
     *
     * @param int $customerId The unique identifier of the customer.
     *
     * @return Customer The found Customer entity.
     * @throws CustomerNotFoundException If the customer is not found.
     */
    public function find(int $customerId): Customer
    {
        $record = DB::table($this->getTableName())
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->first();

        if (!$record) {
            throw CustomerNotFoundException::instance($customerId);
        }

        $propertyDetails = new PropertyDetails(
            landSqFt: (float) $record->land_sqft,
            buildingSqFt: (float) $record->building_sqft,
            livingSqFt: (float) $record->living_sqft,
        );

        return new Customer(id: (int) $record->customer_id, propertyDetails: $propertyDetails);
    }

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
    public function save(Customer $customer): void
    {
        DB::table($this->getTableName())
            ->where('customer_id', $customer->getId())
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $propertyDetails = $customer->getPropertyDetails();
        DB::table($this->getTableName())->insert([
            'customer_id' => $customer->getId(),
            'land_sqft' => $propertyDetails->getLandSqFt(),
            'building_sqft' => $propertyDetails->getBuildingSqFt(),
            'living_sqft' => $propertyDetails->getLivingSqFt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
