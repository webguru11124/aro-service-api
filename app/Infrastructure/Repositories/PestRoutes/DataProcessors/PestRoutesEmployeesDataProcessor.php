<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Illuminate\Support\Collection;

class PestRoutesEmployeesDataProcessor implements EmployeesDataProcessor
{
    use PestRoutesClientAware;

    private const IVR_SCHEDULER_FNAME = 'IVR';

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchEmployeesParams $params
     *
     * @return Collection<PestRoutesEmployee>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchEmployeesParams $params): Collection
    {
        $pestRoutesEmployees = $this->getClient()->office($officeId)
            ->employees()
            ->includeData()
            ->search($params)
            ->all();

        /** @var Collection<PestRoutesEmployee> $pestRoutesEmployees */
        $pestRoutesEmployees = new Collection($pestRoutesEmployees->items);

        return $pestRoutesEmployees;
    }

    /**
     * @param int $officeId
     * @param CreateEmployeesParams $createEmployeesParams
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function create(int $officeId, CreateEmployeesParams $createEmployeesParams): int
    {
        return $this->getClient()
            ->office($officeId)
            ->employees()
            ->create($createEmployeesParams);
    }

    /**
     * @param int $officeId
     *
     * @return PestRoutesEmployee
     * @throws IVRSchedulerNotFoundException
     * @throws InternalServerErrorHttpException
     */
    public function extractIVRScheduler(int $officeId): PestRoutesEmployee
    {
        $params = new SearchEmployeesParams(
            officeIds: [$officeId],
            firstName: self::IVR_SCHEDULER_FNAME
        );

        $ivrScheduler = $this->extract($officeId, $params)->first();

        if ($ivrScheduler === null) {
            throw IVRSchedulerNotFoundException::instance();
        }

        return $ivrScheduler;
    }
}
