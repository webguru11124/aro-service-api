<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\SharedKernel\Entities\Employee;
use Illuminate\Support\Collection;
use App\Domain\Contracts\Queries\Params\EmployeeInfoQueryParams;

interface EmployeeInfoQuery
{
    /**
     * Fetches data based on provided parameters.
     *
     * @param EmployeeInfoQueryParams $params
     *
     * @return Collection<Employee>
     */
    public function get(EmployeeInfoQueryParams $params): Collection;
}
