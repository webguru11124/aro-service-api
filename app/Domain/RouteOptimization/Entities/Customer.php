<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;

class Customer
{
    /**
     * @param int $id Unique identifier for the customer.
     * @param PropertyDetails|null $propertyDetails The property details associated with the customer.
     */
    public function __construct(
        private int $id,
        private PropertyDetails|null $propertyDetails = null
    ) {
    }

    /**
     * Updates the property details of the customer.
     *
     * @param PropertyDetails $propertyDetails The new property details.
     */
    public function setPropertyDetails(PropertyDetails $propertyDetails): void
    {
        $this->propertyDetails = $propertyDetails;
    }

    /**
     * Retrieves the property details of the customer.
     *
     * @return PropertyDetails|null The current property details.
     */
    public function getPropertyDetails(): PropertyDetails|null
    {
        return $this->propertyDetails;
    }

    /**
     * Retrieves the unique identifier of the customer.
     *
     * @return int The customer's ID.
     */
    public function getId(): int
    {
        return $this->id;
    }
}
