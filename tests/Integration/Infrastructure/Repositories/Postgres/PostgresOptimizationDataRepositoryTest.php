<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Infrastructure\Repositories\Postgres\PostgresOptimizationDataRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\OptimizationStateSeeder;

class PostgresOptimizationDataRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresOptimizationDataRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresOptimizationDataRepository();

        $this->seed([
            OptimizationStateSeeder::class,
        ]);
    }

    /**
     * @test
     *
     * ::findOffices
     */
    public function it_finds_offices(): void
    {
        $result = $this->repository->findOffices();

        $this->assertCheckResult($result, ['office_id', 'office']);
    }

    /**
     * @test
     *
     * ::searchExecutionsByDate
     */
    public function it_searches_execution_by_date(): void
    {
        $date = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            OptimizationStateSeeder::getOptimizationStatesMockData()['created_at'][0]
        );
        $result = $this->repository->searchExecutionsByDate($date);

        $this->assertCheckResult($result, ['state_id', 'as_of_date', 'office_id', 'recorded_at', 'created_at', 'success']);
    }

    /**
     * @test
     *
     * ::searchByPreviousStateIds
     */
    public function it_searches_states_by_previous_state_ids(): void
    {
        $result = $this->repository->searchByPreviousStateIds([10000]);

        $this->assertCheckResult($result, ['id', 'created_at', 'previous_state_id', 'state', 'stats', 'metrics', 'weather_forecast', 'rules', 'status']);
    }

    /**
     * @test
     *
     * ::findStatesIdsByOfficeIdAndDate
     */
    public function it_finds_state_id_by_office_and_date(): void
    {
        $optimizationDate = Carbon::parse(
            OptimizationStateSeeder::getOptimizationStatesMockData()['as_of_date'][0]
        );
        $office = json_decode(
            OptimizationStateSeeder::getOptimizationStatesMockData()['office'][0]
        );

        $result = $this->repository->findStatesIdsByOfficeIdAndDate($office->office_id, $optimizationDate);

        $this->assertCheckResult($result, ['id', 'created_at']);
    }

    /**
     * @test
     *
     * ::searchByIds
     */
    public function it_searches_by_id(): void
    {
        $stateIds = OptimizationStateSeeder::getOptimizationStatesMockData()['id'];

        $result = $this->repository->searchByIds($stateIds);

        $this->assertCheckResult($result, ['id', 'created_at', 'previous_state_id', 'state', 'stats', 'metrics', 'weather_forecast', 'rules', 'status']);
    }

    protected function assertCheckResult(Collection $result, array $keys): void
    {
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThan(0, $result->count());

        foreach ($result as $item) {
            $item = json_decode(json_encode($item), true);

            $this->checkArrayHasKeys($keys, $item);
        }
    }

    protected function checkArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }
}
