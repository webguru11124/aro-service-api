<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Application\Commands\ResetPreferredTech\ResetPreferredTechCommand;
use App\Application\Commands\ResetPreferredTech\ResetPreferredTechHandler;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobEnded;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobFailed;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobStarted;
use App\Application\Events\SchedulingSkipped;
use App\Domain\Contracts\Repositories\ScheduledRouteRepository;
use App\Domain\Contracts\Repositories\SchedulingStateRepository;
use App\Domain\Contracts\Services\Actions\ReserveTimeForCalendarEvents;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\Factories\SchedulingStateFactory;
use App\Domain\Scheduling\Services\AppointmentSchedulingService;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduleAppointmentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const OPTIMIZE_ROUTES_DAYS_AFTER_SCHEDULE = 9;

    /**
     * The number of seconds the job can run before timing out.
     *
     * When working with AWS SQS and timeout is greater than 30 sec then make sure that Visibility Timeout in AWS SQS is set to the maximum time
     * that it takes application to process and delete a message from the queue.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    private SchedulingState $schedulingState;
    private SchedulingStateFactory $schedulingStateFactory;
    private AppointmentSchedulingService $schedulingService;
    private ScheduledRouteRepository $scheduledRouteRepository;
    private SchedulingStateRepository $schedulingStateRepository;
    private ResetPreferredTechHandler $resetPreferredTechHandler;
    private ReserveTimeForCalendarEvents $reserveTimeForCalendarEvents;

    public function __construct(
        public readonly CarbonInterface $date,
        public readonly Office $office,
        public readonly int $numDaysToSchedule = 1,
        public readonly bool $runSubsequentOptimization = false,
    ) {
        $this->onQueue(config('queue.queues.schedule-appointments'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        SchedulingStateFactory $schedulingStateFactory,
        AppointmentSchedulingService $schedulingService,
        ScheduledRouteRepository $scheduledRouteRepository,
        SchedulingStateRepository $schedulingStateRepository,
        ResetPreferredTechHandler $resetPreferredTechHandler,
        ReserveTimeForCalendarEvents $reserveTimeForCalendarEvents
    ): void {
        ScheduleAppointmentsJobStarted::dispatch($this->office, $this->date, $this->job);

        $this->schedulingStateFactory = $schedulingStateFactory;
        $this->schedulingService = $schedulingService;
        $this->scheduledRouteRepository = $scheduledRouteRepository;
        $this->schedulingStateRepository = $schedulingStateRepository;
        $this->resetPreferredTechHandler = $resetPreferredTechHandler;
        $this->reserveTimeForCalendarEvents = $reserveTimeForCalendarEvents;

        $numDaysToOptimize = max(1, $this->numDaysToSchedule);

        while ($numDaysToOptimize > 0) {
            try {
                $this->reserveTimeForCalendarEvents->execute($this->office, $this->date);
                $this->resolveSchedulingState();
                $this->processScheduling();
                $this->processResignedTechs();
                $this->storeSchedulingState();
                $this->logSchedulingStats();
                $this->persistScheduledServices();
                $this->executeRoutesOptimization();
            } catch (NoRegularRoutesFoundException|NoServiceProFoundException $exception) {
                Log::notice($exception->getMessage());
                SchedulingSkipped::dispatch($this->office, $this->date, $exception);
            } catch (Throwable $exception) {
                ScheduleAppointmentsJobFailed::dispatch($this->office, $this->date->clone(), $this->job, $exception);
            }

            $numDaysToOptimize--;
            $this->date->addDay();
        }

        ScheduleAppointmentsJobEnded::dispatch($this->office, $this->date, $this->job);
    }

    /**
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     */
    private function resolveSchedulingState(): void
    {
        $this->schedulingState = $this->schedulingStateFactory->create($this->date, $this->office);
    }

    private function processScheduling(): void
    {
        $this->schedulingService->schedulePendingServices($this->schedulingState);
    }

    private function processResignedTechs(): void
    {
        if ($this->schedulingState->getResignedTechAssignments()->isNotEmpty()) {
            $this->resetPreferredTechHandler->handle($this->createResetPreferredCommand());
            $this->schedulingState->resetPreferredTechId();
        }
    }

    private function createResetPreferredCommand(): ResetPreferredTechCommand
    {
        return new ResetPreferredTechCommand(
            $this->schedulingState->getResignedTechAssignments(),
            $this->office->getId(),
        );
    }

    private function storeSchedulingState(): void
    {
        $this->schedulingStateRepository->save($this->schedulingState);
    }

    private function logSchedulingStats(): void
    {
        Log::info(__('messages.automated_scheduling.scheduling_stats', [
            'office_id' => $this->office->getId(),
            'office' => $this->office->getName(),
            'date' => $this->date->toDateString(),
        ]), $this->schedulingState->getStats()->toArray());
    }

    private function executeRoutesOptimization(): void
    {
        if (!$this->runSubsequentOptimization) {
            return;
        }

        $optimizeRoutesDate = Carbon::today($this->office->getTimezone())->addDays(self::OPTIMIZE_ROUTES_DAYS_AFTER_SCHEDULE);
        $scheduledServicesCount = $this->schedulingState->getStats()->scheduledServicesCount;

        if ($this->date->lessThan($optimizeRoutesDate) || $scheduledServicesCount === 0) {
            Log::info(__('messages.automated_scheduling.skip_subsequent_routes_optimization', [
                'office_id' => $this->office->getId(),
                'office' => $this->office->getName(),
                'date' => $this->date->toDateString(),
            ]));

            return;
        }

        OptimizeRoutesJob::dispatch($this->date, $this->office, new OptimizationParams());

        Log::info(__('messages.automated_scheduling.subsequent_routes_optimization_initiated', [
            'office_id' => $this->office->getId(),
            'office' => $this->office->getName(),
            'date' => $this->date->toDateString(),
        ]));
    }

    private function persistScheduledServices(): void
    {
        $this->schedulingState->getScheduledRoutes()->each(
            fn (ScheduledRoute $scheduledRoute) => $this->scheduledRouteRepository->save($scheduledRoute)
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        ScheduleAppointmentsJobFailed::dispatch($this->office, $this->date, $this->job, $exception);
    }
}
