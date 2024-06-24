<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Office;

use App\Domain\Calendar\Entities\Employee;
use Illuminate\Support\Collection;

interface OfficeEmployeeQuery
{
    /**
     * Fetches employees for the office by office ID.
     *
     * @param int $officeId
     *
     * @return Collection<Employee>
     */
    public function find(int $officeId): Collection;
}
