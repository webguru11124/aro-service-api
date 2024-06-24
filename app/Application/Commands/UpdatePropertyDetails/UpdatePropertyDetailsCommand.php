<?php

declare(strict_types=1);

namespace App\Application\Commands\UpdatePropertyDetails;

class UpdatePropertyDetailsCommand
{
    /**
     * Constructor for UpdatePropertyDetailsCommand.
     *
     * @param int $customerId The ID of the customer.
     * @param float $landSqft The square footage of the land.
     * @param float $buildingSqft The square footage of the building.
     * @param float $livingSqft The square footage of the living space.
     * @param int|null $id
     */
    public function __construct(
        public readonly int $customerId,
        public readonly float $landSqft,
        public readonly float $buildingSqft,
        public readonly float $livingSqft,
        public readonly int|null $id = null,
    ) {
    }
}
