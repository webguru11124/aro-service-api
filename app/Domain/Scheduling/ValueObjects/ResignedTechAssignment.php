<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\ValueObjects;

class ResignedTechAssignment
{
    public function __construct(
        public int $customerId,
        public string $customerName,
        public string|null $customerEmail,
        public int $subscriptionId,
        public int $preferredTechId,
    ) {
    }
}
