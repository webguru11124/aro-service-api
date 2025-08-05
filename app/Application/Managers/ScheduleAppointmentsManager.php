<?php

declare(strict_types=1);

namespace App\Application\Managers;

use App\Application\DTO\ScheduleAppointmentsDTO;
use App\Application\Jobs\ScheduleAppointmentsJob;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ScheduleAppointmentsManager
{
    private const APPOINTMENT_SCHEDULING_ENABLED_FOR_OFFICE_FEATURE_FLAG = 'isAppointmentSchedulingForOfficeEnabled';

    private ScheduleAppointmentsDTO $dto;

    /** @var Collection<Office> */
    private Collection $offices;

    public function __construct(
        private readonly GetOfficesByIdsQuery $officesByIdsQuery,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Dispatches a job to schedule appointments for a given date and office.
     *
     * @param ScheduleAppointmentsDTO $dto
     *
     * @return void
     * @throws OfficeNotFoundException
     */
    public function manage(ScheduleAppointmentsDTO $dto): void
    {
        $this->dto = $dto;

        $this->resolveOfficesEnabledForScheduling();
        $this->runAppointmentSchedulingJobs();
    }

    /**
     * @throws OfficeNotFoundException
     */
    private function resolveOfficesEnabledForScheduling(): void
    {
        $this->offices = $this->officesByIdsQuery
            ->get($this->dto->officeIds)
            ->filter(function (Office $office) {
                if (!$this->isAutomatedSchedulingForOfficeEnabled($office)) {
                    Log::notice(__('messages.automated_scheduling.scheduling_is_disabled', [
                        'office' => $office->getName(),
                        'office_id' => $office->getId(),
                    ]));

                    return false;
                }

                return true;
            });
    }

    private function runAppointmentSchedulingJobs(): void
    {
        foreach ($this->offices as $office) {
            $date = $this->resolveDateForOffice($office);

            ScheduleAppointmentsJob::dispatch(
                $date->clone(),
                $office,
                $this->dto->numDaysToSchedule,
                $this->dto->runSubsequentOptimization
            );

            Log::info(__('messages.automated_scheduling.initiated', [
                'office' => $office->getName(),
                'office_id' => $office->getId(),
                'date' => $date->toDateString(),
                'num_days_to_schedule' => $this->dto->numDaysToSchedule,
                'run_subsequent_optimization' => $this->dto->runSubsequentOptimization,
            ]));
        }
    }

    private function resolveDateForOffice(Office $office): CarbonInterface
    {
        if (!empty($this->dto->startDate)) {
            return Carbon::parse(
                $this->dto->startDate->toFormattedDateString(),
                $office->getTimezone()
            );
        }

        $date = Carbon::today($office->getTimezone());

        if (!empty($this->dto->numDaysAfterStartDate)) {
            $date->addDays($this->dto->numDaysAfterStartDate);
        }

        return $date;
    }

    private function isAutomatedSchedulingForOfficeEnabled(Office $office): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $office->getId(),
            self::APPOINTMENT_SCHEDULING_ENABLED_FOR_OFFICE_FEATURE_FLAG
        );
    }
}
