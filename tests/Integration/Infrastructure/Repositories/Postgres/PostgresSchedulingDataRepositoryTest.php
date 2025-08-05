<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Infrastructure\Repositories\Postgres\PostgresSchedulingDataRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\SchedulingStateSeeder;

class PostgresSchedulingDataRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PostgresSchedulingDataRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresSchedulingDataRepository();

        $this->seed([
            SchedulingStateSeeder::class,
        ]);
    }

    /**
     * @test
     */
    public function it_searches_by_ids(): void
    {
        $ids = SchedulingStateSeeder::getSchedulingStatesDataMock()['id'];
        $result = $this->repository->searchByIds($ids);

        $this->assertCheckResult($result, ['id', 'as_of_date', 'created_at', 'pending_services']);
    }

    /**
     * @test
     */
    public function it_searches_executions_by_date(): void
    {
        $date = Carbon::parse(SchedulingStateSeeder::getSchedulingStatesDataMock()['created_at'][0]);
        $result = $this->repository->searchExecutionsByDate($date);

        $this->assertCheckResult($result, ['id', 'as_of_date', 'office_id', 'stats', 'created_at']);
    }

    /**
     * @test
     */
    public function it_finds_states_by_office_id_and_date(): void
    {
        $officeId = SchedulingStateSeeder::getSchedulingStatesDataMock()['id'][0];
        $optimizationDate = Carbon::createFromFormat(
            'Y-m-d',
            SchedulingStateSeeder::getSchedulingStatesDataMock()['as_of_date'][0]
        );
        $result = $this->repository->findStatesByOfficeIdAndDate($officeId, $optimizationDate);

        $this->assertCheckResult($result, ['id', 'stats', 'created_at']);
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
