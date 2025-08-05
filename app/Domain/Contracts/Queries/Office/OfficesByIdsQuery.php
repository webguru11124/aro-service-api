<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Office;

use App\Domain\SharedKernel\Entities\Office;
use Illuminate\Support\Collection;

interface OfficesByIdsQuery
{
    /**
     * Gets a collection of offices by their ids
     *
     * @param int ...$officeIds
     *
     * @return Collection<Office>
     */
    public function get(int ...$officeIds): Collection;
}
