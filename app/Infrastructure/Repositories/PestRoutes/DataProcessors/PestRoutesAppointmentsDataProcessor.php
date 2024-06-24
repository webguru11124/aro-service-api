<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Exceptions\UpdateLockedAppointmentException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\UpdateAppointmentsParams;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;

class PestRoutesAppointmentsDataProcessor implements AppointmentsDataProcessor
{
    use PestRoutesClientAware;

    private const LOCKED_APPOINTMENT_MESSAGE = 'Appointment is locked and is not allowed to change';

    public function __construct(
        private PestRoutesClient $client,
        private OfficesDataProcessor $officesDataProcessor
    ) {
    }

    /**
     * Extracts appointments based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchAppointmentsParams $params
     *
     * @return Collection<PestRoutesAppointment>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchAppointmentsParams $params): Collection
    {
        /** @var PestRoutesOffice $pestRoutesOffice */
        $pestRoutesOffice = $this->officesDataProcessor->extract($officeId, new SearchOfficesParams(
            officeId: $officeId
        ))->first();

        $officesCollection = new PestRoutesCollection([$pestRoutesOffice]);

        $pestRoutesAppointments = $this->getClient()
            ->office($officeId)
            ->appointments()
            ->includeData()
            ->search($params, $officesCollection)
            ->all();

        /** @var Collection<PestRoutesAppointment> $pestRoutesAppointments */
        $pestRoutesAppointments = new Collection($pestRoutesAppointments->items);

        return $pestRoutesAppointments;
    }

    /**
     * Updates an appointment based on the office ID and update parameters.
     *
     * @param int $officeId
     * @param UpdateAppointmentsParams $params
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function update(int $officeId, UpdateAppointmentsParams $params): bool
    {
        try {
            return (bool) $this->getClient()
                ->office($officeId)
                ->appointments()
                ->update($params);
        } catch (\Exception $e) {

            if (strpos($e->getMessage(), self::LOCKED_APPOINTMENT_MESSAGE) !== false) {
                throw UpdateLockedAppointmentException::instance();
            }

            throw $e;
        }
    }

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
    public function assignAppointment(int $officeId, int $routeId, int $appointmentId, int|null $spotId = null): bool
    {
        return $this->update($officeId, new UpdateAppointmentsParams(
            appointmentId: $appointmentId,
            spotId: $spotId,
            routeId: $routeId,
            officeId: $officeId
        ));
    }

    /**
     * Creates an appointment
     *
     * @param int $officeId
     * @param CreateAppointmentsParams $params
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function create(int $officeId, CreateAppointmentsParams $params): int
    {
        return $this->getClient()
            ->office($officeId)
            ->appointments()
            ->create($params);
    }

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
    public function cancel(int $officeId, int $id, string|null $reason = null, int|null $canceledBy = null): int
    {
        return $this->getClient()
            ->office($officeId)
            ->appointments()
            ->cancel($id, $reason, $canceledBy);
    }
}
