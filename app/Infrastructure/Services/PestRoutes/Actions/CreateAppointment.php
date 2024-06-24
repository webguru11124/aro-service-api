<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Actions;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Dto\CreateAppointmentDto;
use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use App\Infrastructure\Exceptions\SpotNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\CreateAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;

class CreateAppointment
{
    private const DEFAULT_DURATION = 30;

    private Office $office;

    public function __construct(
        private readonly PestRoutesAppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly EmployeesDataProcessor $employeesDataProcessor,
        private readonly SpotsDataProcessor $spotsDataProcessor
    ) {

    }

    /**
     * @param CreateAppointmentDto $dto
     *
     * @return int
     * @throws InternalServerErrorHttpException
     * @throws IVRSchedulerNotFoundException
     * @throws SpotNotFoundException
     */
    public function __invoke(CreateAppointmentDto $dto): int
    {
        $this->office = $dto->office;
        $spot = $this->findSpot($dto->spotId);
        $ivrScheduler = $this->employeesDataProcessor->extractIVRScheduler($this->office->getId());
        [$startTime, $endTime] = $this->timeFromWindow($dto->window, Carbon::instance($spot->start)->toDateString());

        $notes = sprintf('Scheduled via %s', $dto->requestingSource->value);

        if ($dto->notes !== null) {
            $notes .= ' - ' . $dto->notes;
        }

        if ($dto->isAroSpot) {
            $this->spotsDataProcessor->unblock($this->office->getId(), $dto->spotId);
        }

        return $this->appointmentsDataProcessor->create($this->office->getId(), new CreateAppointmentsParams(
            customerId: $dto->customerId,
            typeId: $dto->appointmentType->toServiceType()->value,
            start: $startTime,
            end: $endTime,
            duration: self::DEFAULT_DURATION,
            employeeId: $ivrScheduler->id,
            notes: $notes,
            spotId: $dto->spotId,
            subscriptionId: $dto->subscriptionId,
            rejectOccupiedSpots: true,
            rejectFixedOccupiedSpots: true,
            officeId: $this->office->getId()
        ));
    }

    /**
     * @param Window $window
     * @param string $date
     *
     * @return \DateTime[]
     */
    private function timeFromWindow(Window $window, string $date): array
    {
        $timeZone = $this->office->getTimeZone();

        return match ($window) {
            Window::AM => [
                Carbon::parse($date . ' 08:00:00', $timeZone),
                Carbon::parse($date . ' 12:00:00', $timeZone),
            ],
            Window::PM => [
                Carbon::parse($date . ' 12:00:00', $timeZone),
                Carbon::parse($date . ' 17:00:00', $timeZone),
            ]
        };
    }

    /**
     * @throws SpotNotFoundException
     */
    private function findSpot(int $spotId): PestRoutesSpot
    {
        $spot = $this->spotsDataProcessor->extract($this->office->getId(), new SearchSpotsParams(
            officeIds: [$this->office->getId()],
            ids: [$spotId]
        ))->first();

        if ($spot === null) {
            throw SpotNotFoundException::instance($spotId);
        }

        return $spot;
    }
}
