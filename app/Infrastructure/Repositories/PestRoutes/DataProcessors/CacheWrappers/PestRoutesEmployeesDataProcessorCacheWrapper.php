<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Illuminate\Support\Collection;

class PestRoutesEmployeesDataProcessorCacheWrapper extends AbstractCachedWrapper implements EmployeesDataProcessor
{
    /**
     * @var PestRoutesEmployeesDataProcessor
     */
    protected mixed $wrapped;

    private const CACHE_TTL = [
        'extractIVRScheduler' => 2592000, //30 days
    ];

    private const CACHE_PREFIX = 'PestRoutesEmployees_';

    public function __construct(PestRoutesEmployeesDataProcessor $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchEmployeesParams $params
     *
     * @return Collection<PestRoutesEmployee>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchEmployeesParams $params): Collection
    {
        return $this->wrapped->extract($officeId, $params);
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
        return $this->wrapped->create($officeId, $createEmployeesParams);
    }

    /**
     * @param int $officeId
     *
     * @return PestRoutesEmployee
     */
    public function extractIVRScheduler(int $officeId): PestRoutesEmployee
    {
        return $this->cached(__FUNCTION__, $officeId);
    }

    protected function getCacheTtl(string $methodName): int
    {
        return self::CACHE_TTL[$methodName];
    }

    protected function getPrefix(): string
    {
        return self::CACHE_PREFIX;
    }
}
