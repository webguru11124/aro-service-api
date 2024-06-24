<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Illuminate\Support\Collection;

interface AppointmentsDataProcessor
{
    /**
     * Extracts appointments based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchAppointmentsParams $params
     *
     * @return Collection<PestRoutesAppointment>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchAppointmentsParams $params): Collection;

    /**
     * Updates an appointment based on the office ID and update parameters.
     *
     * @param int $officeId
     * @param UpdateAppointmentsParams $params
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function update(int $officeId, UpdateAppointmentsParams $params): bool;

    /**
     * Creates an appointment
     *
     * @param int $officeId
     * @param CreateAppointmentsParams $params
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function create(int $officeId, CreateAppointmentsParams $params): int;

    /**
     * Assigns an appointment to a route
     *
     * @param int $officeId
     * @param int $routeId
     * @param int $appointmentId
     * @param int|null $spotId
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function assignAppointment(int $officeId, int $routeId, int $appointmentId, int|null $spotId = null): bool;

    /**
     * Cancels an appointment
     *
     * @param int $officeId
     * @param int $id
     * @param string|null $reason
     * @param int|null $canceledBy
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function cancel(int $officeId, int $id, string|null $reason = null, int|null $canceledBy = null): int;
}
