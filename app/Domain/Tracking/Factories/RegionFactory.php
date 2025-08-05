<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Factories;

use App\Domain\SharedKernel\Entities\Region;

class RegionFactory
{
    /**
     * Creates a region entity from an array of data.
     *
     * @param array<string, mixed> $data
     *
     * @return Region
     */
    public function create(array $data): Region
    {
        return new Region(
            is_string($data['id']) ? (int) $data['id'] : $data['id'],
            $data['name'],
        );
    }
}
