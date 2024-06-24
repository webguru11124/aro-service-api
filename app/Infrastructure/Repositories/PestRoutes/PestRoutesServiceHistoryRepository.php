<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use App\Domain\Contracts\Repositories\ServiceHistoryRepository;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceHistoryTranslator;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PestRoutesServiceHistoryRepository implements ServiceHistoryRepository
{
    public function __construct(
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly ServiceTypesDataProcessor $serviceTypesDataProcessor,
        private readonly PestRoutesServiceHistoryTranslator $translator
    ) {
    }

    /**
     * @param int $officeId
     * @param int ...$customerIds
     *
     * @return Collection
     * @throws InternalServerErrorHttpException
     */
    public function searchByCustomerIdAndOfficeId(int $officeId, int ...$customerIds): Collection
    {
        $pestRoutesAppointments = $this->appointmentsDataProcessor->extract($officeId, new SearchAppointmentsParams(
            officeIds: [$officeId],
            status: AppointmentStatus::Completed,
            customerIds: $customerIds
        ));

        if ($pestRoutesAppointments->isEmpty()) {
            return new Collection();
        }

        $serviceTypeIds = $pestRoutesAppointments
            ->pluck('serviceTypeId')
            ->filter()
            ->unique()
            ->toArray();

        $indexedPestRoutesServiceTypes = $this->serviceTypesDataProcessor
            ->extract($officeId, new SearchServiceTypesParams(
                ids: $serviceTypeIds,
                officeIds: [$officeId]
            ))->keyBy('id');

        $callback = function (PestRoutesAppointment $pestRoutesAppointment) use ($indexedPestRoutesServiceTypes) {
            $pestRoutesServiceType = $indexedPestRoutesServiceTypes->get($pestRoutesAppointment->serviceTypeId);
            if (!$pestRoutesServiceType) {
                $appointmentParameters = [
                    'appointment_id' => $pestRoutesAppointment->id,
                    'date' => (new Carbon($pestRoutesAppointment->checkIn))->toDateString(),
                    'office_id' => $pestRoutesAppointment->officeId,
                    'service_type_id' => $pestRoutesAppointment->serviceTypeId,
                ];
                Log::warning(__('messages.appointment.unknown_service_type_id', $appointmentParameters));

                return null;
            }

            return $this->translator->toDomain($pestRoutesAppointment, $pestRoutesServiceType);
        };

        return $pestRoutesAppointments->map($callback)->filter();
    }
}
