<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;

interface ServiceHistoryRepository
{
    /**
     * @param int $officeId
     * @param int ...$customerIds
     *
     * @return Collection
     */
    public function searchByCustomerIdAndOfficeId(int $officeId, int ...$customerIds): Collection;
}
