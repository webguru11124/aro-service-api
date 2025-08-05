<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\Scheduling\Entities\Customer;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;

class PestRoutesCustomerTranslator
{
    public function toDomain(PestRoutesCustomer $customer): Customer
    {
        return new Customer(
            $customer->id,
            $customer->firstName . ' ' . $customer->lastName,
            new Coordinate($customer->latitude, $customer->longitude),
            $customer->email,
            $customer->preferredTechId
        );
    }
}
