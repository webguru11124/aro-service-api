<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminder as PestRoutesAppointmentReminders;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\CreateAppointmentRemindersParams;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\SearchAppointmentRemindersParams;
use Illuminate\Support\Collection;

interface AppointmentRemindersDataProcessor
{
    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchAppointmentRemindersParams $params
     *
     * @return Collection<PestRoutesAppointmentReminders>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchAppointmentRemindersParams $params): Collection;

    /**
     * @param int $officeId
     * @param CreateAppointmentRemindersParams $params
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function create(int $officeId, CreateAppointmentRemindersParams $params): int;
}
