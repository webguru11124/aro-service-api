<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday\Queries\Cached;

use App\Domain\Contracts\Queries\EmployeeInfoQuery;
use App\Domain\SharedKernel\Entities\Employee;
use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Services\Workday\Queries\WorkdayEmployeeInfoQuery;
use Illuminate\Support\Collection;
use App\Domain\Contracts\Queries\Params\EmployeeInfoQueryParams;

class WorkdayEmployeeInfoCachedQuery extends AbstractCachedWrapper implements EmployeeInfoQuery
{
    private const CACHE_TTL = 60 * 60 * 24;
    private const CACHE_PREFIX = 'WorkdayEmployeeInfoQuery_';

    public function __construct(WorkdayEmployeeInfoQuery $query)
    {
        $this->wrapped = $query;
    }

    /**
     * @param EmployeeInfoQueryParams $params
     *
     * @return Collection<Employee>
     */
    public function get(EmployeeInfoQueryParams $params): Collection
    {
        return $this->cached(__FUNCTION__, $params);
    }

    protected function getCacheTtl(string $methodName): int
    {
        return self::CACHE_TTL;
    }

    protected function getPrefix(): string
    {
        return self::CACHE_PREFIX;
    }
}
