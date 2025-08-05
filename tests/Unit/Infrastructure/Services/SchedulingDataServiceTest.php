<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services;

use App\Infrastructure\Repositories\Postgres\PostgresSchedulingDataRepository;
use App\Infrastructure\Services\SchedulingDataService;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;
use Tests\Tools\TestValue;

class SchedulingDataServiceTest extends TestCase
{
    private MockInterface|PostgresSchedulingDataRepository $mockRepository;
    private SchedulingDataService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(PostgresSchedulingDataRepository::class);
        $this->service = new SchedulingDataService($this->mockRepository);
    }

    /**
     * @test
     *
     * ::getStateData
     */
    public function it_gets_state_data(): void
    {
        $state = new stdClass();
        $state->id = TestValue::SCHEDULING_STATE_ID;
        $state->as_of_date = '2024-03-13';
        $state->office_id = TestValue::OFFICE_ID;
        $state->pending_services = '{}';

        $scheduledRoute = new stdClass();
        $scheduledRoute->route_id = TestValue::ROUTE_ID;
        $scheduledRoute->details = '{}';
        $scheduledRoute->pending_services = '{}';
        $scheduledRoute->appointments = '{}';
        $scheduledRoute->service_pro = '{}';

        $this->mockRepository
            ->shouldReceive('searchByIds')
            ->with([TestValue::SCHEDULING_STATE_ID])
            ->once()
            ->andReturn(collect([$state]));
        $this->mockRepository
            ->shouldReceive('searchScheduledRoutesByStateId')
            ->with($state->id)
            ->once()
            ->andReturn(collect([$scheduledRoute]));

        $result = $this->service->getStateData($state->id);

        $this->assertEquals(TestValue::SCHEDULING_STATE_ID, $result['id']);
        $this->assertEquals($state->as_of_date, $result['as_of_date']);
        $this->assertEquals(json_decode($state->pending_services, true), $result['pending_services']);

        $resultScheduledRoute = $result['scheduled_routes'][0];
        $this->assertEquals(TestValue::ROUTE_ID, $resultScheduledRoute['id']);
    }

    /**
     * @test
     *
     * ::getSchedulingOverview
     */
    public function it_returns_empty_when_getting_scheduling_overview(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-13 00:00:00');

        $this->mockRepository
            ->shouldReceive('findStatesByOfficeIdAndDate')
            ->with(TestValue::OFFICE_ID, $date)
            ->once()
            ->andReturn(collect());

        $result = $this->service->getSchedulingOverview(TestValue::OFFICE_ID, $date);

        $this->assertCount(0, $result);
    }

    /**
     * @test
     *
     * ::getSchedulingExecutions
     */
    public function it_returns_states_when_getting_scheduling_executions(): void
    {
        $date = Carbon::parse('2024-03-06 12:00:00');
        $dateMatchingAmericaDenverTimezone = $date->copy()->setTimezone('America/Denver');
        $fakeStats = [
            'routes_count' => 3,
            'pending_services_count' => 100,
            'total_assigned_services' => 68,
            'scheduled_services_count' => 15,
            'capacity_after_scheduling' => 0,
            'capacity_before_scheduling' => 32,
            'total_appointments_before_scheduling' => 5,
        ];
        $allStates = collect([
            (object) [
                'created_at' => $date->timestamp,
                'stats' => $fakeStats,
                'id' => 1,
            ],
            (object) [
                'created_at' => $date->timestamp,
                'stats' => $fakeStats,
                'id' => 2,
            ],
        ]);

        $this->mockRepository
            ->shouldReceive('searchExecutionsByDate')
            ->once()
            ->andReturn($allStates);

        $result = $this->service->getSchedulingExecutions($date);
        $this->assertCount(2, $result);

        $this->assertEquals(
            [
                'id' => 1,
                'created_at' => $dateMatchingAmericaDenverTimezone->format('H:i:s'),
                'stats' => $fakeStats,
            ],
            $result->first(),
        );
    }

    /**
     * @test
     */
    public function it_returns_states_when_getting_scheduling_overview(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-15 00:00:00');
        $matchingDateDayForAmericaDenverTimezone = Carbon::parse('2024-03-06 12:00:00');
        $fakeStats = [
            'routes_count' => 3,
            'pending_services_count' => 100,
            'total_assigned_services' => 68,
            'scheduled_services_count' => 15,
            'capacity_after_scheduling' => 0,
            'capacity_before_scheduling' => 32,
            'total_appointments_before_scheduling' => 5,
        ];
        $allStates = collect([
            (object) [
                'created_at' => $matchingDateDayForAmericaDenverTimezone,
                'stats' => $fakeStats,
                'id' => 1,
            ],
            (object) [
                'created_at' => $date,
                'stats' => $fakeStats,
                'id' => 2,
            ],
        ]);

        $this->mockRepository
            ->shouldReceive('findStatesByOfficeIdAndDate')
            ->with(TestValue::OFFICE_ID, $date)
            ->once()
            ->andReturn($allStates);

        $result = $this->service->getSchedulingOverview(TestValue::OFFICE_ID, $date);
        $this->assertCount(2, $result);

        $date->setTimezone('America/Denver');
        $matchingDateDayForAmericaDenverTimezone->setTimezone('America/Denver');

        $this->assertEmpty($result[$date->format('Y-m-d')]);
        $this->assertEquals(
            [
                'id' => 1,
                'created_at' => $matchingDateDayForAmericaDenverTimezone->format('Y-m-d'),
                'stats' => $fakeStats,
                'start_at' => $matchingDateDayForAmericaDenverTimezone->format('H:i:s'),
            ],
            $result[$matchingDateDayForAmericaDenverTimezone->format('Y-m-d')]->first()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockRepository);
        unset($this->service);
    }
}
