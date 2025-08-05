<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CachableAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Illuminate\Support\Collection;

class PestRoutesAppointmentsDataProcessorCacheWrapper extends AbstractCachedWrapper implements AppointmentsDataProcessor, CachableAppointmentsDataProcessor
{
    private const CACHE_TTL = [
        'extract' => 86400, // 24 hours
    ];
    private const CACHE_PREFIX = 'PestRoutesAppointment_';

    public function __construct(PestRoutesAppointmentsDataProcessor $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchAppointmentsParams $params
     *
     * @return Collection<PestRoutesAppointment>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchAppointmentsParams $params): Collection
    {
        return $this->cached(__FUNCTION__, $officeId, $params);
    }

    /**
     *
     * @param int $officeId
     * @param UpdateAppointmentsParams $params
     *
     * @return bool
     */
    public function update(int $officeId, UpdateAppointmentsParams $params): bool
    {
        return $this->wrapped->update($officeId, $params);
    }

    /**
     *
     * @param int $officeId
     * @param CreateAppointmentsParams $params
     *
     * @return int
     */
    public function create(int $officeId, CreateAppointmentsParams $params): int
    {
        return $this->wrapped->create($officeId, $params);
    }

    /**
     *
     * @param int $officeId
     * @param int $routeId
     * @param int $appointmentId
     * @param int|null $spotId
     *
     * @return bool
     */
    public function assignAppointment(int $officeId, int $routeId, int $appointmentId, int|null $spotId = null): bool
    {
        return $this->wrapped->assignAppointment($officeId, $routeId, $appointmentId, $spotId);
    }

    /**
     *
     * @param int $officeId
     * @param int $id
     * @param string|null $reason
     * @param int|null $canceledBy
     *
     * @return int
     */
    public function cancel(int $officeId, int $id, string|null $reason = null, int|null $canceledBy = null): int
    {
        return $this->wrapped->cancel($officeId, $id, $reason, $canceledBy);
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
