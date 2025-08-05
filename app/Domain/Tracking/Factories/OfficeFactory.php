<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonTimeZone;

class OfficeFactory
{
    /**
     * Creates an office entity from an array of data.
     *
     * @param array<string, mixed> $data
     *
     * @return Office
     */
    public function create(array $data): Office
    {
        return new Office(
            is_string($data['officeID']) ? (int) $data['officeID'] : $data['officeID'],
            $data['officeName'],
            $data['region'],
            new Address(
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip'] ?? null,
            ),
            new CarbonTimeZone($data['timeZone']),
            new Coordinate($data['location'][0], $data['location'][1]),
        );
    }
}
