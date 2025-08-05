<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Managers;

use App\Application\DTO\ScoreNotificationsDTO;
use App\Application\Jobs\SendNotificationsJob;
use App\Application\Managers\ScoreNotificationsManager;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
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

class ScoreNotificationsManagerTest extends TestCase
{
    private const QUEUE_NAME = 'TestQueue';

    private MockInterface|GetOfficesByIdsQuery $mockOfficesByIdsQuery;
    private ScoreNotificationsManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('queue.queues.send-notifications', self::QUEUE_NAME);
        Queue::fake();

        $this->mockOfficesByIdsQuery = Mockery::mock(GetOfficesByIdsQuery::class);

        $this->manager = new ScoreNotificationsManager(
            $this->mockOfficesByIdsQuery,
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
    public function it_dispatches_job(Carbon $date, array $officeIds): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $this->mockOfficesByIdsQuery
            ->shouldReceive('get')
            ->with($officeIds)
            ->once()
            ->andReturn($this->getOfficeCollection($officeIds));

        Log::shouldReceive('info')->once();

        $this->manager->manage(new ScoreNotificationsDTO($officeIds, $date));

        Queue::assertPushed(SendNotificationsJob::class);
        Queue::assertPushedOn(self::QUEUE_NAME, SendNotificationsJob::class);
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
    public function it_dispatches_job_for_today(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));
        $officeIds = [TestValue::OFFICE_ID];

        $this->mockOfficesByIdsQuery
            ->shouldReceive('get')
            ->with($officeIds)
            ->once()
            ->andReturn($this->getOfficeCollection($officeIds));

        Log::shouldReceive('info')->once();

        $this->manager->manage(new ScoreNotificationsDTO($officeIds, null));

        Queue::assertPushedOn(self::QUEUE_NAME, SendNotificationsJob::class);
        Queue::assertPushed(SendNotificationsJob::class, function (SendNotificationsJob $job) use ($officeIds) {
            $officeIdsFromJob = $job->offices->map(
                function (Office $office) {
                    return $office->getId();
                },
            )->toArray();

            return $job->date->toDateString() == Carbon::today(TestValue::TZ)->toDateString()
                && $officeIdsFromJob === $officeIds;
        });
    }

    /**
     * @param int[] $officeIds
     *
     * @return Collection<Office>
     */
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
        unset($this->mockOfficesByIdsQuery);
    }
}
