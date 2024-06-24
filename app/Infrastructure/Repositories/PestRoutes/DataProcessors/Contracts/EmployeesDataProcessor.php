<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Illuminate\Support\Collection;

interface EmployeesDataProcessor
{
    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchEmployeesParams $params
     *
     * @return Collection<PestRoutesEmployee>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchEmployeesParams $params): Collection;

    /**
     * @param int $officeId
     * @param CreateEmployeesParams $createEmployeesParams
     *
     * @return int
     */
    public function create(int $officeId, CreateEmployeesParams $createEmployeesParams): int;

    /**
     * @param int $officeId
     *
     * @return PestRoutesEmployee
     * @throws IVRSchedulerNotFoundException
     */
    public function extractIVRScheduler(int $officeId): PestRoutesEmployee;
}
