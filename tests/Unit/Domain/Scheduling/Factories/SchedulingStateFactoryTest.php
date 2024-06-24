<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Factories;

use App\Domain\Calendar\Entities\Employee;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use App\Domain\Contracts\Queries\PlansQuery;
use App\Domain\Contracts\Repositories\PendingServiceRepository;
use App\Domain\Contracts\Repositories\RescheduledPendingServiceRepository;
use App\Domain\Contracts\Repositories\ScheduledRouteRepository;
use App\Domain\Contracts\Repositories\SchedulingStateRepository;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\Factories\SchedulingStateFactory;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\PlanFactory;
use Tests\Tools\Factories\Scheduling\ScheduledRouteFactory;

class SchedulingStateFactoryTest extends TestCase
{
    private SchedulingStateFactory $factory;
    private PlansQuery|MockInterface $plansQueryMock;
    private SchedulingStateRepository|MockInterface $schedulingStateRepositoryMock;
    private PendingServiceRepository|MockInterface $pendingServicesRepositoryMock;
    private ScheduledRouteRepository|MockInterface $scheduledRouteRepositoryMock;
    private RescheduledPendingServiceRepository|MockInterface $rescheduledPendingServiceRepositoryMock;
    private OfficeEmployeeQuery|MockInterface $officeEmployeeQueryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMocks();

        $this->factory = new SchedulingStateFactory(
            $this->plansQueryMock,
            $this->schedulingStateRepositoryMock,
            $this->pendingServicesRepositoryMock,
            $this->scheduledRouteRepositoryMock,
            $this->rescheduledPendingServiceRepositoryMock,
            $this->officeEmployeeQueryMock
        );
    }

    protected function setUpMocks(): void
    {
        $this->plansQueryMock = Mockery::mock(PlansQuery::class);
        $this->schedulingStateRepositoryMock = Mockery::mock(SchedulingStateRepository::class);
        $this->pendingServicesRepositoryMock = Mockery::mock(PendingServiceRepository::class);
        $this->scheduledRouteRepositoryMock = Mockery::mock(ScheduledRouteRepository::class);
        $this->rescheduledPendingServiceRepositoryMock = Mockery::mock(RescheduledPendingServiceRepository::class);
        $this->officeEmployeeQueryMock = Mockery::mock(OfficeEmployeeQuery::class);
    }

    /**
     * @test
     */
    public function it_creates_scheduling_state(): void
    {
        $date = Carbon::now();
        $office = OfficeFactory::make();

        $expectedNextId = 10;
        $expectedPlans = PlanFactory::all();
        $expectedPendingServices = $this->generateExpectedPendingServices($expectedPlans, $date, $office);
        $expectedRescheduledPendingServices = PendingServiceFactory::many(2);
        $expectedScheduledRoutes = ScheduledRouteFactory::many(2);
        $expectedActiveEmployeeIds = [1, 2, 3];

        $this->schedulingStateRepositoryMock->shouldReceive('getNextId')
            ->once()
            ->andReturn($expectedNextId);

        $this->plansQueryMock->shouldReceive('get')
            ->once()
            ->andReturn(collect($expectedPlans));

        $this->scheduledRouteRepositoryMock->shouldReceive('findByOfficeIdAndDate')
            ->with($office, $date)
            ->once()
            ->andReturn(collect($expectedScheduledRoutes));

        $this->rescheduledPendingServiceRepositoryMock->shouldReceive('findByOfficeIdAndDate')
            ->with($office, $date)
            ->once()
            ->andReturn(collect($expectedRescheduledPendingServices));

        $this->officeEmployeeQueryMock->shouldReceive('find')
            ->with($office->getId())
            ->once()
            ->andReturn(collect([
                new Employee(1, 'John Doe'),
                new Employee(2, 'Jane Doe'),
                new Employee(3, 'Mike Smith'),
            ]));

        /** @var SchedulingState $schedulingState */
        $schedulingState = $this->factory->create($date, $office);

        $this->assertInstanceOf(SchedulingState::class, $schedulingState);
        $this->assertEquals($expectedNextId, $schedulingState->getId());
        $this->assertEquals($date, $schedulingState->getDate());
        $this->assertEquals($office, $schedulingState->getOffice());
        $this->assertEquals($expectedScheduledRoutes, $schedulingState->getScheduledRoutes()->all());
        $this->assertEquals(array_merge($expectedPendingServices, $expectedRescheduledPendingServices), $schedulingState->getPendingServices()->all());
        $this->assertEquals($expectedActiveEmployeeIds, $schedulingState->getAllActiveEmployeeIds());
    }

    private function generateExpectedPendingServices(array $plans, Carbon $date, Office $office): array
    {
        $pendingServices = [];

        foreach ($plans as $plan) {
            $pendingService = PendingServiceFactory::make([
                'office' => $office,
                'date' => $date,
                'plan' => $plan,
            ]);

            $this->pendingServicesRepositoryMock->shouldReceive('findByOfficeIdAndDate')
                ->with($office, $date, $plan)
                ->once()
                ->andReturn(collect([$pendingService]));
            $pendingServices[] = $pendingService;
        }

        return $pendingServices;
    }
}
