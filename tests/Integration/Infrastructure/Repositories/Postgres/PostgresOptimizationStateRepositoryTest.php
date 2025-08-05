<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Factories\OptimizationStateArrayFactory;
use App\Domain\RouteOptimization\Services\OptimizationStateStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\OptimizationStateStats;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Formatters\OptimizationStateArrayFormatter;
use App\Infrastructure\Formatters\RouteArrayFormatter;
use App\Infrastructure\Repositories\Postgres\PostgresOptimizationStateRepository;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\OptimizationStateSeeder;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;

class PostgresOptimizationStateRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresOptimizationStateRepository $repository;

    private MockInterface|OptimizationStateArrayFormatter $mockOptimizationStateArrayFormatter;
    private MockInterface|OptimizationStateStatisticsService $mockOptimizationStateStatisticsService;
    private MockInterface|RouteArrayFormatter $mockRouteArrayFormatter;
    private MockInterface|OptimizationStateArrayFactory $mockOptimizationStateArrayFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOptimizationStateArrayFormatter = Mockery::mock(OptimizationStateArrayFormatter::class);
        $this->mockOptimizationStateStatisticsService = Mockery::mock(OptimizationStateStatisticsService::class);
        $this->mockRouteArrayFormatter = Mockery::mock(RouteArrayFormatter::class);
        $this->mockOptimizationStateArrayFactory = Mockery::mock(OptimizationStateArrayFactory::class);

        $this->repository = new PostgresOptimizationStateRepository(
            $this->mockOptimizationStateArrayFormatter,
            $this->mockOptimizationStateStatisticsService,
            $this->mockRouteArrayFormatter,
            $this->mockOptimizationStateArrayFactory,
        );
    }

    /**
     * @test
     *
     * ::getNextId
     */
    public function it_can_get_the_next_id(): void
    {
        $id = $this->repository->getNextId();

        $this->assertIsInt($id);
    }

    /**
     * @test
     *
     * ::findById
     */
    public function it_finds_state_by_id(): void
    {
        $optimizationState = OptimizationStateFactory::make([
            'id' => TestValue::OPTIMIZATION_STATE_ID,
            'routes' => collect(),
        ]);
        $this->mockOptimizationStateArrayFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($optimizationState);

        $this->seed([
            OptimizationStateSeeder::class,
        ]);

        $state = $this->repository->findById(10000);

        $this->assertInstanceOf(OptimizationState::class, $state);
        $this->assertEquals($state, $optimizationState);
    }

    /**
     * @test
     *
     * ::save
     */
    public function it_updates_an_optimization_if_one_exists_in_db_when_saving(): void
    {
        $optimizationState = $this->getOptimizationState();
        $routeId = $optimizationState->getRoutes()->first()->getId();
        $optimizationState->getRoutes()->first()->setGeometry(TestValue::GEOMETRY);

        // insert the original optimization state to DB
        $stubPreUpdateArrayFormat = ['some_data' => 'PRE_UPDATE', 'stubId' => 12345];
        DB::table(PostgresDBInfo::OPTIMIZATION_STATE_TABLE)
            ->insert([
                'id' => $optimizationState->getId(),
                'as_of_date' => Carbon::now()->toDateString(),
                'state' => json_encode($stubPreUpdateArrayFormat),
            ]);

        $stubStateStats = $this->getOptimizationStats();

        // Change an attribute
        $stubPostUpdateArrayFormat = [
            'office' => ['office_id' => TestValue::OFFICE_ID],
            'state' => ['date' => '2024-01-01'],
            'rules' => ['some_rule' => 1],
            'metrics' => ['score' => 1],
            'weather' => ['wind' => 10],
        ];
        $stubRouteArrayFormat = [
            'schedule' => ['appointment' => '2024-01-01'],
            'details' => ['capacity' => 10],
            'service_pro' => ['service_pro_id' => TestValue::EMPLOYEE_ID],
            'metrics' => ['score' => 1],
            'stats' => $stubStateStats->toArray(),
        ];

        $this->mockOptimizationStateArrayFormatter
            ->shouldReceive('format')
            ->once()
            ->with($optimizationState)
            ->andReturn($stubPostUpdateArrayFormat);
        $this->mockOptimizationStateStatisticsService
            ->shouldReceive('getStats')
            ->once()
            ->with($optimizationState)
            ->andReturn($stubStateStats);
        $this->mockRouteArrayFormatter
            ->shouldReceive('format')
            ->once()
            ->andReturn($stubRouteArrayFormat);

        $this->repository->save($optimizationState);

        $this->assertDatabaseHas(PostgresDBInfo::OPTIMIZATION_STATE_TABLE, [
            'id' => $optimizationState->getId(),
            'state' => json_encode($stubPostUpdateArrayFormat['state']),
            'office' => json_encode($stubPostUpdateArrayFormat['office']),
            'rules' => json_encode($stubPostUpdateArrayFormat['rules']),
            'metrics' => json_encode($stubPostUpdateArrayFormat['metrics']),
            'weather_forecast' => json_encode($stubPostUpdateArrayFormat['weather']),
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas(PostgresDBInfo::ROUTE_DETAILS_TABLE, [
            'optimization_state_id' => $optimizationState->getId(),
            'route_id' => $routeId,
            'schedule' => json_encode($stubRouteArrayFormat['schedule']),
            'details' => json_encode($stubRouteArrayFormat['details']),
            'service_pro' => json_encode($stubRouteArrayFormat['service_pro']),
            'metrics' => json_encode($stubRouteArrayFormat['metrics']),
            'stats' => json_encode($stubStateStats->toArray()),
        ]);
        $this->assertDatabaseHas(PostgresDBInfo::ROUTE_GEOMETRY_TABLE, [
            'optimization_state_id' => $optimizationState->getId(),
            'route_id' => $routeId,
            'geometry' => TestValue::GEOMETRY,
        ]);
    }

    /**
     * @test
     */
    public function it_creates_an_optimization_if_one_does_not_exist_in_the_db_when_saving(): void
    {
        $optimizationState = $this->getOptimizationState();
        $routeId = $optimizationState->getRoutes()->first()->getId();
        $stubStateStats = $this->getOptimizationStats();

        $stubArrayFormat = [
            'office' => ['office_id' => TestValue::OFFICE_ID],
            'state' => ['date' => '2024-01-01'],
            'rules' => ['some_rule' => 1],
            'metrics' => ['score' => 1],
            'weather' => ['wind' => 10],
        ];
        $stubRouteArrayFormat = [
            'schedule' => [],
            'details' => [],
            'service_pro' => [],
            'metrics' => [],
            'stats' => $stubStateStats->toArray(),
        ];

        $this->mockOptimizationStateArrayFormatter
            ->shouldReceive('format')
            ->once()
            ->with($optimizationState)
            ->andReturn($stubArrayFormat);
        $this->mockOptimizationStateStatisticsService
            ->shouldReceive('getStats')
            ->once()
            ->with($optimizationState)
            ->andReturn($stubStateStats);
        $this->mockRouteArrayFormatter
            ->shouldReceive('format')
            ->once()
            ->andReturn($stubRouteArrayFormat);

        $this->repository->save($optimizationState);

        $this->assertDatabaseHas(PostgresDBInfo::OPTIMIZATION_STATE_TABLE, [
            'id' => $optimizationState->getId(),
            'state' => json_encode($stubArrayFormat['state']),
            'office' => json_encode($stubArrayFormat['office']),
            'rules' => json_encode($stubArrayFormat['rules']),
            'metrics' => json_encode($stubArrayFormat['metrics']),
            'weather_forecast' => json_encode($stubArrayFormat['weather']),
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas(PostgresDBInfo::ROUTE_DETAILS_TABLE, [
            'optimization_state_id' => $optimizationState->getId(),
            'route_id' => $routeId,
            'schedule' => json_encode($stubRouteArrayFormat['schedule']),
            'details' => json_encode($stubRouteArrayFormat['details']),
            'service_pro' => json_encode($stubRouteArrayFormat['service_pro']),
            'metrics' => json_encode($stubRouteArrayFormat['metrics']),
            'stats' => json_encode($stubStateStats->toArray()),
        ]);
    }

    private function getOptimizationStats(): OptimizationStateStats
    {
        return new OptimizationStateStats(
            $this->faker->randomNumber(2),
            $this->faker->randomNumber(1),
            $this->faker->randomNumber(1),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
            $this->faker->randomFloat(2, 2, 5),
            $this->faker->randomFloat(2, 5, 10),
            Duration::fromMinutes($this->faker->randomNumber(3)),
            Distance::fromMeters($this->faker->randomNumber(4)),
        );
    }

    private function getOptimizationState(): OptimizationState
    {
        return OptimizationStateFactory::make([
            'routes' => [RouteFactory::make()],
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->repository,
            $this->mockOptimizationStateArrayFormatter,
            $this->mockOptimizationStateStatisticsService,
            $this->mockRouteArrayFormatter,
            $this->mockOptimizationStateArrayFactory
        );
    }
}
