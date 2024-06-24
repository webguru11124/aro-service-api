<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Infrastructure\Formatters\PendingServiceArrayFormatter;
use App\Infrastructure\Formatters\ScheduledRouteArrayFormatter;
use App\Infrastructure\Repositories\Postgres\PostgresSchedulingStateRepository;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\ScheduledRouteFactory;
use Tests\Tools\Factories\Scheduling\SchedulingStateFactory;

class PostgresSchedulingStateRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresSchedulingStateRepository $repository;

    private MockInterface|PendingServiceArrayFormatter $mockPendingServiceArrayFormatter;
    private MockInterface|ScheduledRouteArrayFormatter $mockScheduledRouteArrayFormatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPendingServiceArrayFormatter = Mockery::mock(PendingServiceArrayFormatter::class);
        $this->mockScheduledRouteArrayFormatter = Mockery::mock(ScheduledRouteArrayFormatter::class);

        $this->repository = new PostgresSchedulingStateRepository(
            $this->mockPendingServiceArrayFormatter,
            $this->mockScheduledRouteArrayFormatter,
        );
    }

    /**
     * @test
     *
     * ::getNextId
     */
    public function it_gets_the_next_id(): void
    {
        $id = $this->repository->getNextId();

        $this->assertIsInt($id);
    }

    /**
     * @test
     *
     * ::save
     */
    public function it_saves_scheduling_state(): void
    {
        /** @var ScheduledRoute $scheduledRoute */
        $scheduledRoute = ScheduledRouteFactory::make();
        /** @var PendingService $pendingService */
        $pendingService = PendingServiceFactory::make();

        /** @var SchedulingState $schedulingState */
        $schedulingState = SchedulingStateFactory::make([
            'scheduledRoutes' => collect([$scheduledRoute]),
            'pendingServices' => collect([$pendingService]),
        ]);

        $stubPendingServicesArrayFormat = ['pending service'];
        $this->mockPendingServiceArrayFormatter
            ->shouldReceive('format')
            ->once()
            ->with($pendingService)
            ->andReturn($stubPendingServicesArrayFormat);

        $stubScheduledRouteArrayFormat = [
            'details' => [],
            'appointments' => [],
            'pending_services' => [],
            'service_pro' => [],
        ];
        $this->mockScheduledRouteArrayFormatter
            ->shouldReceive('format')
            ->once()
            ->with($scheduledRoute)
            ->andReturn($stubScheduledRouteArrayFormat);

        $this->repository->save($schedulingState);

        $this->assertDatabaseHas(PostgresDBInfo::SCHEDULING_STATES_TABLE, [
            'id' => $schedulingState->getId(),
            'as_of_date' => $schedulingState->getDate()->toDateString(),
            'office_id' => $schedulingState->getOffice()->getId(),
            'pending_services' => json_encode([$stubPendingServicesArrayFormat]),
            'stats' => json_encode($schedulingState->getStats()->toArray()),
        ]);

        $this->assertDatabaseHas(PostgresDBInfo::SCHEDULED_ROUTE_DETAILS, [
            'scheduling_state_id' => $schedulingState->getId(),
            'route_id' => $scheduledRoute->getId(),
        ]);
    }
}
