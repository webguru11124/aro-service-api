<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services;

use App\Infrastructure\Repositories\Postgres\PostgresOptimizationDataRepository;
use App\Infrastructure\Services\OptimizationDataService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\JsonHelper;
use Tests\Tools\TestValue;

class OptimizationDataServiceTest extends TestCase
{
    private MockInterface|PostgresOptimizationDataRepository $mockRepository;
    private OptimizationDataService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(PostgresOptimizationDataRepository::class);
        $this->service = new OptimizationDataService($this->mockRepository);
    }

    /**
     * @test
     *
     * ::getOffices
     */
    public function it_gets_offices(): void
    {
        $this->mockRepository
            ->shouldReceive('findOffices')
            ->with()
            ->once()
            ->andReturn(collect());

        $this->service->getOffices();
    }

    /**
     * @test
     *
     * ::getOptimizationExecutions
     */
    public function it_gets_optimization_executions(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '2021-03-01 00:00:00');
        $this->mockRepository
            ->shouldReceive('searchExecutionsByDate')
            ->once()
            ->andReturn(collect());

        $result = $this->service->getOptimizationExecutions($date);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @test
     *
     * ::getOptimizationStateDetails
     */
    public function it_gets_state_data(): void
    {
        $state = $this->getMockState(Carbon::now());

        $this->mockRepository
            ->shouldReceive('searchByIds')
            ->with([$state['id']])
            ->once()
            ->andReturn(collect([$state]));

        $this->mockRepository
            ->shouldReceive('searchByPreviousStateIds')
            ->with([$state['id']])
            ->once()
            ->andReturn(collect());

        $result = $this->service->getOptimizationStateDetails($state['id']);

        $this->assertNotEmpty($result['pre_state']['id']);
    }

    /**
     * @test
     *
     * ::getOptimizationOverview
     */
    public function it_returns_empty_when_getting_optimization_overview(): void
    {
        $optimizationDate = Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-13 00:00:00');

        $this->mockRepository
            ->shouldReceive('findStatesIdsByOfficeIdAndDate')
            ->with(TestValue::OFFICE_ID, $optimizationDate)
            ->once()
            ->andReturn(collect());

        $result = $this->service->getOptimizationOverview(TestValue::OFFICE_ID, $optimizationDate);

        $this->assertCount(0, $result);
    }

    /**
     * @test
     *
     * ::getOptimizationOverview
     */
    public function it_returns_states_when_getting_optimization_overview(): void
    {
        $optimizationDate = Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-15 00:00:00');
        $matchingDateDayForAmericaDenverTimezone = Carbon::parse('2024-03-06 12:00:00');

        $matchingState = $this->getMockState($matchingDateDayForAmericaDenverTimezone);
        $notMatchingState = JsonHelper::cloneWith($matchingState, ['id' => 2, 'created_at' => $optimizationDate]);
        $preStates = collect([
            (object) $matchingState,
            (object) $notMatchingState,
        ]);
        $allStates = [
            $this->getState($matchingDateDayForAmericaDenverTimezone),
        ];

        $this->mockRepository
            ->shouldReceive('findStatesIdsByOfficeIdAndDate')
            ->with(TestValue::OFFICE_ID, $optimizationDate)
            ->once()
            ->andReturn($preStates);

        $this->mockRepository
            ->shouldReceive('searchByIds')
            ->with([1])
            ->once()
            ->andReturn(collect($allStates));

        $this->mockRepository
            ->shouldReceive('searchByPreviousStateIds')
            ->with([1])
            ->once()
            ->andReturn(collect());

        $result = $this->service->getOptimizationOverview(TestValue::OFFICE_ID, $optimizationDate);

        $this->assertCount(2, $result);

        $optimizationDate->setTimezone('America/Denver');
        $matchingDateDayForAmericaDenverTimezone->setTimezone('America/Denver');

        $this->assertEmpty($result[$optimizationDate->format('Y-m-d')]);
        $this->assertNotEmpty($result[$matchingDateDayForAmericaDenverTimezone->format('Y-m-d')]->first());
        $this->checkArrayHasKeys(
            [
                'created_at',
                'start_at',
                'pre_state_id',
                'routes',
            ],
            $result[$matchingDateDayForAmericaDenverTimezone->format('Y-m-d')]->first(),
        );
    }

    protected function getMockState(CarbonInterface $date): array
    {
        return [
            'id' => 1,
            'created_at' => $date,
        ];
    }

    /**
     * @return mixed[]
     */
    private function getState(CarbonInterface $date): array
    {
        return [
            'id' => 1,
            'state' => [
                'created_at' => $date,
                'engine' => 'engine',
                'unassigned_appointments' => [],
            ],
            'stats' => $this->getMockPostStats(),
            'routes' => [],
        ];
    }

    /**
     * @return mixed[]
     */
    private function getMockPostStats(): array
    {
        return [
            'total_assigned_appointments' => 10,
            'metrics' => [
                'total_routes' => 5,
                'total_drive_time' => 120,
                'services_per_hour' => 2.5,
                'total_drive_miles' => 60.5,
                'average_daily_working_hours' => 10.5,
            ],
            'unassigned_appointments' => [
                ['id' => 1, 'client' => 'Client A'],
                ['id' => 2, 'client' => 'Client B'],
            ],
            'routes' => [
                ['id' => 101, 'start' => 'Location A', 'end' => 'Location B'],
                ['id' => 102, 'start' => 'Location C', 'end' => 'Location D'],
            ],
        ];
    }

    private function checkArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockRepository);
        unset($this->service);
    }
}
