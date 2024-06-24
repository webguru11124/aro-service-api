<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Commands\ResetPreferredTech\ResetPreferredTechHandler;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobEnded;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobFailed;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobStarted;
use App\Application\Events\SchedulingSkipped;
use App\Application\Jobs\OptimizeRoutesJob;
use App\Application\Jobs\ScheduleAppointmentsJob;
use App\Domain\Scheduling\Entities\Customer;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\Factories\SchedulingStateFactory;
use App\Domain\Scheduling\Services\AppointmentSchedulingService;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesScheduledRouteRepository;
use App\Infrastructure\Repositories\Postgres\PostgresSchedulingStateRepository;
use App\Infrastructure\Services\PestRoutes\Actions\PestRoutesReserveTimeForCalendarEvents;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\ScheduledRouteFactory;
use Tests\Tools\TestValue;

class ScheduleAppointmentsJobTest extends TestCase
{
    private Carbon $date;

    private MockInterface|SchedulingStateFactory $mockSchedulingStateFactory;
    private MockInterface|AppointmentSchedulingService $mockSchedulingService;
    private MockInterface|PestRoutesScheduledRouteRepository $mockScheduledRouteRepository;
    private MockInterface|PostgresSchedulingStateRepository $mockSchedulingStateRepository;
    private MockInterface|ResetPreferredTechHandler $mockResetCustomerPreferredTechHandler;
    private MockInterface|PestRoutesReserveTimeForCalendarEvents $mockReserveTimeForCalendarEvents;

    private SchedulingState $schedulingState;
    private ScheduleAppointmentsJob $job;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Queue::fake();

        $this->mockSchedulingStateFactory = Mockery::mock(SchedulingStateFactory::class);
        $this->mockSchedulingService = Mockery::mock(AppointmentSchedulingService::class);
        $this->mockScheduledRouteRepository = Mockery::mock(PestRoutesScheduledRouteRepository::class);
        $this->mockSchedulingStateRepository = Mockery::mock(PostgresSchedulingStateRepository::class);
        $this->mockResetCustomerPreferredTechHandler = Mockery::mock(ResetPreferredTechHandler::class);
        $this->mockReserveTimeForCalendarEvents = Mockery::mock(PestRoutesReserveTimeForCalendarEvents::class);

        $this->schedulingState = \Tests\Tools\Factories\Scheduling\SchedulingStateFactory::make();

        $this->date = Carbon::today();
        $this->office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
            'name' => TestValue::OFFICE_NAME,
        ]);
        $this->job = new ScheduleAppointmentsJob($this->date, $this->office);
    }

    /**
     * @test
     */
    public function it_processes_scheduling(): void
    {
        $activeProIds = [1001];
        $scheduledRoute = $this->generateScheduledRoute(1000);
        /** @var SchedulingState $schedulingState */
        $schedulingState = \Tests\Tools\Factories\Scheduling\SchedulingStateFactory::make([
            'scheduledRoutes' => collect([$scheduledRoute]),
            'allActiveServiceProIds' => $activeProIds,
        ]);

        $this->setMockSchedulingStateFactoryExpectations($this->date->clone(), $schedulingState);
        $this->setMockSchedulingServiceExpectations($schedulingState);
        $this->setMockSchedulingStateRepositoryExpectations($schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office);

        $this->mockScheduledRouteRepository
            ->shouldReceive('save')
            ->with($scheduledRoute)
            ->once();

        $this->mockResetCustomerPreferredTechHandler
            ->shouldReceive('handle')
            ->once();

        Log::shouldReceive('info')->once();

        $this->job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Event::assertNotDispatched(SchedulingSkipped::class);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_processes_scheduling_for_several_days_out(): void
    {
        $numOfDaysToSchedule = 3;

        $this->setMockSchedulingStateFactoryExpectations($this->date->clone(), $this->schedulingState);
        $this->setMockSchedulingStateFactoryExpectations($this->date->clone()->addDay(), $this->schedulingState);
        $this->setMockSchedulingStateFactoryExpectations($this->date->clone()->addDays(2), $this->schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office, $numOfDaysToSchedule);

        $this->mockSchedulingService
            ->shouldReceive('schedulePendingServices')
            ->times($numOfDaysToSchedule)
            ->andReturn($this->schedulingState);

        $this->mockSchedulingStateRepository
            ->shouldReceive('save')
            ->times($numOfDaysToSchedule);

        $this->mockScheduledRouteRepository
            ->shouldReceive('save')
            ->never();

        $this->mockResetCustomerPreferredTechHandler
            ->shouldReceive('handle')
            ->never();

        Log::shouldReceive('info')->times($numOfDaysToSchedule);

        $job = new ScheduleAppointmentsJob($this->date->clone(), $this->office, $numOfDaysToSchedule);
        $job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_logs_notice_and_processes_scheduling_for_next_days_out_if_one_day_has_data_validation_exception(): void
    {
        $numOfDaysToSchedule = 3;
        $possibleExceptions = [
            NoRegularRoutesFoundException::class,
            NoServiceProFoundException::class,
        ];
        $exceptionClass = $possibleExceptions[random_int(0, count($possibleExceptions) - 1)];

        $this->setMockSchedulingStateFactoryExpectations($this->date->clone(), $this->schedulingState);
        $this->mockSchedulingStateFactory
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (CarbonInterface $date, Office $office) {
                return $office == $this->office
                    && $date->toDateString() === $this->date->clone()->addDay()->toDateString();
            })
            ->andThrow(new $exceptionClass('test'));
        $this->setMockSchedulingStateFactoryExpectations($this->date->clone()->addDays(2), $this->schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office, $numOfDaysToSchedule);

        $this->mockSchedulingService
            ->shouldReceive('schedulePendingServices')
            ->times($numOfDaysToSchedule - 1)
            ->andReturn($this->schedulingState);

        $this->mockSchedulingStateRepository
            ->shouldReceive('save')
            ->times($numOfDaysToSchedule - 1);

        Log::shouldReceive('info')->times($numOfDaysToSchedule - 1);
        Log::shouldReceive('notice')->once();

        $job = new ScheduleAppointmentsJob($this->date->clone(), $this->office, $numOfDaysToSchedule);

        $job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Event::assertNotDispatched(ScheduleAppointmentsJobFailed::class);
        Event::assertDispatched(SchedulingSkipped::class);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_processes_scheduling_for_next_days_out_if_one_failed(): void
    {
        $numOfDaysToSchedule = 3;

        $this->setMockSchedulingStateFactoryExpectations($this->date->clone(), $this->schedulingState);
        $this->mockSchedulingStateFactory
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (CarbonInterface $date, Office $office) {
                return $office == $this->office
                    && $date->toDateString() === $this->date->clone()->addDay()->toDateString();
            })
            ->andThrow(Exception::class);
        $this->setMockSchedulingStateFactoryExpectations($this->date->clone()->addDays(2), $this->schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office, $numOfDaysToSchedule);

        $this->mockSchedulingService
            ->shouldReceive('schedulePendingServices')
            ->times($numOfDaysToSchedule - 1)
            ->andReturn($this->schedulingState);

        $this->mockSchedulingStateRepository
            ->shouldReceive('save')
            ->times($numOfDaysToSchedule - 1);

        Log::shouldReceive('info')->times($numOfDaysToSchedule - 1);

        $job = new ScheduleAppointmentsJob($this->date->clone(), $this->office, $numOfDaysToSchedule);

        $job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Event::assertDispatched(ScheduleAppointmentsJobFailed::class);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_dispatches_routes_optimization_job_after_scheduling(): void
    {
        $date = Carbon::today()->addDays(10);
        /** @var PendingService $pendingService */
        $pendingService = PendingServiceFactory::make();
        /** @var ScheduledRoute $scheduledRoute */
        $scheduledRoute = ScheduledRouteFactory::make([
            'pendingServices' => [$pendingService],
        ]);

        /** @var SchedulingState $schedulingState */
        $schedulingState = \Tests\Tools\Factories\Scheduling\SchedulingStateFactory::make([
            'scheduledRoutes' => collect([$scheduledRoute]),
        ]);

        $this->setMockSchedulingStateFactoryExpectations($date->clone(), $schedulingState);
        $this->setMockSchedulingServiceExpectations($schedulingState);
        $this->setMockSchedulingStateRepositoryExpectations($schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office);

        $this->mockScheduledRouteRepository
            ->shouldReceive('save')
            ->with($scheduledRoute)
            ->once();

        Log::shouldReceive('info')->twice();

        $job = new ScheduleAppointmentsJob($date, $this->office, 1, true);
        $job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Queue::assertPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_does_not_dispatches_routes_optimization_job_after_scheduling_when_run_before_10_days(): void
    {
        $date = Carbon::today($this->office->getTimezone())->addDays(8);

        /** @var SchedulingState $schedulingState */
        $schedulingState = \Tests\Tools\Factories\Scheduling\SchedulingStateFactory::make([
            'scheduledRoutes' => collect([
                ScheduledRouteFactory::make([
                    'pendingServices' => [PendingServiceFactory::make()],
                ]),
            ]),
        ]);

        $this->setMockSchedulingStateFactoryExpectations($date->clone(), $schedulingState);
        $this->setMockSchedulingServiceExpectations($schedulingState);
        $this->setMockSchedulingStateRepositoryExpectations($schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office);

        $this->mockScheduledRouteRepository
            ->shouldReceive('save')
            ->once();

        Log::shouldReceive('info')->twice();

        $job = new ScheduleAppointmentsJob($date, $this->office, 1, true);
        $job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_does_not_dispatches_routes_optimization_job_after_scheduling_when_no_scheduled_routes(): void
    {
        $date = Carbon::today()->addDays(9);

        /** @var SchedulingState $schedulingState */
        $schedulingState = \Tests\Tools\Factories\Scheduling\SchedulingStateFactory::make([
            'scheduledRoutes' => collect(),
        ]);

        $this->setMockSchedulingStateFactoryExpectations($date->clone(), $schedulingState);
        $this->setMockSchedulingServiceExpectations($schedulingState);
        $this->setMockSchedulingStateRepositoryExpectations($schedulingState);
        $this->setMockReserveTimeForCalendarEventExpectations($this->date, $this->office);

        Log::shouldReceive('info')->twice();

        $job = new ScheduleAppointmentsJob($date, $this->office, 1, true);
        $job->handle(
            $this->mockSchedulingStateFactory,
            $this->mockSchedulingService,
            $this->mockScheduledRouteRepository,
            $this->mockSchedulingStateRepository,
            $this->mockResetCustomerPreferredTechHandler,
            $this->mockReserveTimeForCalendarEvents,
        );

        Event::assertDispatched(ScheduleAppointmentsJobStarted::class);
        Event::assertDispatched(ScheduleAppointmentsJobEnded::class);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     *
     * ::failed
     */
    public function it_dispatches_event_on_failure(): void
    {
        $this->job->failed(new Exception('Test'));

        Event::assertDispatched(ScheduleAppointmentsJobFailed::class);
    }

    private function generateScheduledRoute(int $preferredTechIdToBeReturned): ScheduledRoute
    {
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->shouldReceive('getPreferredTechId')->andReturn($preferredTechIdToBeReturned);
        $mockCustomer->shouldReceive('getId')->andReturn(1234);
        $mockCustomer->shouldReceive('getName')->andReturn('Test Customer');
        $mockCustomer->shouldReceive('getEmail')->andReturn('abc@testemail.email');
        $mockPendingService = Mockery::mock(PendingService::class);
        $mockPendingService->shouldReceive('getCustomer')->andReturn($mockCustomer);
        $mockPendingService->shouldReceive('isHighPriority')->andReturn(100);
        $mockPendingService->shouldReceive('resetPreferredEmployeeId')->andReturnSelf();
        $mockPendingService->shouldReceive('getSubscriptionId')->andReturn(TestValue::SUBSCRIPTION_ID);
        $mockPendingService->shouldReceive('getPreferredEmployeeId')->andReturn($preferredTechIdToBeReturned);
        $mockPendingService->shouldReceive('isRescheduled')->andReturnFalse();

        return ScheduledRouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'date' => $this->date,
            'officeId' => TestValue::OFFICE_ID,
            'appointments' => [],
            'pendingServices' => [$mockPendingService],
        ]);
    }

    private function setMockSchedulingStateFactoryExpectations(
        CarbonInterface $expectedDate,
        SchedulingState $returnSchedulingState
    ): void {
        $this->mockSchedulingStateFactory
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (CarbonInterface $date, Office $office) use ($expectedDate) {
                return $office == $this->office && $date->toDateString() === $expectedDate->toDateString();
            })
            ->andReturn($returnSchedulingState);
    }

    private function setMockSchedulingServiceExpectations(SchedulingState $schedulingState): void
    {
        $this->mockSchedulingService
            ->shouldReceive('schedulePendingServices')
            ->with($schedulingState)
            ->once()
            ->andReturn($schedulingState);
    }

    private function setMockSchedulingStateRepositoryExpectations(SchedulingState $schedulingState): void
    {
        $this->mockSchedulingStateRepository
            ->shouldReceive('save')
            ->with($schedulingState)
            ->once();
    }

    private function setMockReserveTimeForCalendarEventExpectations(CarbonInterface $date, Office $office, int $times = 1): void
    {
        $this->mockReserveTimeForCalendarEvents
            ->shouldReceive('execute')
            ->with($office, Mockery::on(function ($arg) {
                return $arg instanceof CarbonInterface;
            }))
            ->times($times);
    }
}
