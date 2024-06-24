<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Managers;

use App\Application\DTO\ServiceStatsDTO;
use App\Application\Jobs\ServiceStatsJob;
use App\Application\Managers\ServiceStatsManager;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class ServiceStatsManagerTest extends TestCase
{
    private const QUEUE_NAME = 'TestQueue';

    private MockInterface|GetOfficesByIdsQuery $mockOfficeQuery;
    private ServiceStatsManager $serviceStatsManager;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('queue.queues.service-stats', self::QUEUE_NAME);
        Queue::fake();

        $this->mockOfficeQuery = Mockery::mock(GetOfficesByIdsQuery::class);

        $this->serviceStatsManager = new ServiceStatsManager(
            $this->mockOfficeQuery,
        );
    }

    /**
     * @test
     *
     * @dataProvider inputDataProvider
     *
     * @param Carbon $date
     * @param int[] $officeIds
     *
     * @return void
     */
    public function it_dispatches_optimization_job(Carbon $date, array $officeIds): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeCollection = $this->getOfficeCollection($officeIds);
        $this->mockOfficeQuery
            ->shouldReceive('get')
            ->withArgs([$officeIds])
            ->once()
            ->andReturn($officeCollection);

        Log::shouldReceive('info')->times(count($officeIds));

        $serviceStatsData = new ServiceStatsDTO($officeIds, $date);
        $this->serviceStatsManager->manage($serviceStatsData);

        Queue::assertPushed(ServiceStatsJob::class);
        Queue::assertPushedOn(self::QUEUE_NAME, ServiceStatsJob::class);
    }

    public static function inputDataProvider(): iterable
    {
        yield [
            Carbon::tomorrow(),
            [1, 2, 3],
        ];
        yield [
            Carbon::tomorrow(),
            [TestValue::OFFICE_ID],
        ];
    }

    /**
     * @test
     */
    public function it_dispatches_optimization_job_for_today(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));
        $officeIds = [TestValue::OFFICE_ID];

        $this->mockOfficeQuery
            ->shouldReceive('get')
            ->withArgs([$officeIds])
            ->once()
            ->andReturn($this->getOfficeCollection($officeIds));

        Log::shouldReceive('info')->once();

        $serviceStatsData = new ServiceStatsDTO($officeIds, null);
        $this->serviceStatsManager->manage($serviceStatsData);

        Queue::assertPushedOn(self::QUEUE_NAME, ServiceStatsJob::class);
        Queue::assertPushed(ServiceStatsJob::class, function (ServiceStatsJob $job) {
            return $job->date->toDateString() == Carbon::today(TestValue::TZ)->toDateString()
                && $job->office->getId() === TestValue::OFFICE_ID;
        });
    }

    private function getOfficeCollection(array $officeIds): Collection
    {
        $officeCollection = new Collection();

        foreach ($officeIds as $officeId) {
            $officeCollection->add(
                OfficeFactory::make(['id' => $officeId, 'timezone' => TestValue::TZ])
            );
        }

        return $officeCollection;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->mockOfficeQuery);
    }
}
