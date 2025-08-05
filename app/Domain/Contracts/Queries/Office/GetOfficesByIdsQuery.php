<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Office;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Illuminate\Support\Collection;

interface GetOfficesByIdsQuery
{
    /**
     * It returns a collection of offices by their ids
     *
     * @param int[] $ids
     *
     * @return Collection<Office>
     * @throws OfficeNotFoundException
     */
    public function get(array $ids): Collection;
}
