<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Actions;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Dto\CreateAppointmentDto;
use App\Infrastructure\Dto\RescheduleAppointmentDto;
use App\Infrastructure\Exceptions\AppointmentNotFoundException;
use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use App\Infrastructure\Exceptions\SpotNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminderStatus;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\CreateAppointmentRemindersParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RescheduleAppointment
{
    private Office $office;

    public function __construct(
        private readonly CreateAppointment $createAppointmentAction,
        private readonly PestRoutesAppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly EmployeesDataProcessor $employeesDataProcessor,
        private readonly PestRoutesAppointmentRemindersDataProcessor $appointmentRemindersDataProcessor
    ) {
    }

    /**
     * @param RescheduleAppointmentDto $dto
     *
     * @return int
     * @throws InternalServerErrorHttpException
     * @throws IVRSchedulerNotFoundException
     * @throws AppointmentNotFoundException
     * @throws SpotNotFoundException
     */
    public function __invoke(RescheduleAppointmentDto $dto): int
    {
        $this->office = $dto->office;

        $this->validateAppointmentExistence($dto->appointmentId);

        $createAppointmentDto = new CreateAppointmentDto(
            office: $dto->office,
            customerId: $dto->customerId,
            spotId: $dto->spotId,
            subscriptionId: $dto->subscriptionId,
            appointmentType: $dto->serviceType->toAppointmentType(),
            isAroSpot: $dto->isAroSpot,
            window: $dto->window,
            requestingSource: $dto->requestingSource
        );

        $newAppointmentId = ($this->createAppointmentAction)($createAppointmentDto);

        $this->createAppointmentReminder($newAppointmentId);
        $this->cancelAppointment($dto->appointmentId);

        return $newAppointmentId;
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    private function createAppointmentReminder(int $appointmentId): void
    {
        $date = Carbon::now($this->office->getTimeZone());

        $this->appointmentRemindersDataProcessor->create($this->office->getId(), new CreateAppointmentRemindersParams(
            appointmentId: $appointmentId,
            text: __('messages.appointment_reminder.created_reschedule'),
            dateSent: $date,
            emailSent: $date,
            status: AppointmentReminderStatus::SENT,
            officeId: $this->office->getId(),
        ));
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    private function cancelAppointment(int $appointmentId): void
    {
        $reason = __('messages.appointment.canceled_rescheduled_ivr');

        try {
            $ivrScheduler = $this->employeesDataProcessor->extractIVRScheduler($this->office->getId());
        } catch (\Exception $e) {
            $ivrScheduler = null;
            Log::warning('Reschedule appointment - WARNING', ['warning' => $e->getMessage()]);
        }

        $this->appointmentsDataProcessor->cancel(
            $this->office->getId(),
            $appointmentId,
            $reason,
            $ivrScheduler?->id
        );
    }

    /**
     * @throws AppointmentNotFoundException
     * @throws InternalServerErrorHttpException
     */
    private function validateAppointmentExistence(int $appointmentId): void
    {
        $oldAppointment = $this->appointmentsDataProcessor
            ->extract($this->office->getId(), new SearchAppointmentsParams(
                officeIds: [$this->office->getId()],
                ids: [$appointmentId]
            ))->first();

        if ($oldAppointment === null) {
            throw AppointmentNotFoundException::instance($appointmentId);
        }
    }
}
