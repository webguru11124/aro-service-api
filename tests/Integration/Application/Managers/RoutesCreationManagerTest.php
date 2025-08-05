<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Managers;

use App\Application\DTO\RouteCreationDTO;
use App\Application\Jobs\RoutesCreationJob;
use App\Application\Managers\RoutesCreationManager;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\Office\OfficesByIdsQuery;
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

class RoutesCreationManagerTest extends TestCase
{
    private const QUEUE_NAME = 'TestQueue';

    private MockInterface|OfficesByIdsQuery $officesByIdsQueryMock;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    private RoutesCreationManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('queue.queues.routes_creation', self::QUEUE_NAME);
        Queue::fake();

        $this->setupMockOfficeRepository();
        $this->setupMockFeatureFlagService();
        $this->manager = new RoutesCreationManager(
            $this->officesByIdsQueryMock,
            $this->mockFeatureFlagService,
        );
    }

    private function setupMockOfficeRepository(): void
    {
        $this->officesByIdsQueryMock = Mockery::mock(OfficesByIdsQuery::class);
    }

    private function setupMockFeatureFlagService(): void
    {
        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
    }

    /**
     * @test
     *
     * @dataProvider inputDataProvider
     *
     * @return void
     */
    public function it_dispatches_routes_creation_job(int|null $numDaysToCreateRoutes): void
    {
        $date = Carbon::tomorrow();
        $officeIds = [1, 2, 3];
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->with(...$officeIds)
            ->once()
            ->andReturn($officeCollection);

        $this->setMockFeatureFlagExpectations();

        if (is_null($numDaysToCreateRoutes)) {
            $routeCreationdata = new RouteCreationDTO(
                officeIds: $officeIds,
                startDate: $date,
            );
            $expectedJobsCount = count($officeIds);
        } else {
            $routeCreationdata = new RouteCreationDTO(
                officeIds: $officeIds,
                startDate: $date,
                numDaysToCreateRoutes: $numDaysToCreateRoutes,
            );
            $expectedJobsCount = count($officeIds) * $numDaysToCreateRoutes;
        }

        $this->manager->manage($routeCreationdata);

        if ($numDaysToCreateRoutes === 0) {
            Queue::assertNotPushed(RoutesCreationJob::class);
        } else {
            Queue::assertPushed(RoutesCreationJob::class, $expectedJobsCount);
            Queue::assertPushedOn(self::QUEUE_NAME, RoutesCreationJob::class);
        }
    }

    /**
     * @test
     */
    public function it_filters_offices_based_on_feature_flag(): void
    {
        $date = Carbon::tomorrow();
        $officeIdsWithEnabledFeatureFlag = [1, 3];
        $officeIds = [1, 2, 3, 4];
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->with(...$officeIds)
            ->once()
            ->andReturn($officeCollection);

        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnUsing(function ($officeId) use ($officeIdsWithEnabledFeatureFlag) {
                return in_array($officeId, $officeIdsWithEnabledFeatureFlag);
            });

        $routeCreationdata = new RouteCreationDTO(
            officeIds: $officeIds,
            startDate: $date,
            numDaysToCreateRoutes: 1,
        );
        Log::shouldReceive('notice')
            ->times(count($officeIds) - count($officeIdsWithEnabledFeatureFlag))
            ->with(Mockery::on(function ($message) {
                return str_contains($message, 'Route creation is not enabled');
            }));
        Log::shouldReceive('info');

        $this->manager->manage($routeCreationdata);

        Queue::assertPushed(RoutesCreationJob::class, count($officeIdsWithEnabledFeatureFlag));
        Queue::assertPushedOn(self::QUEUE_NAME, RoutesCreationJob::class, function ($job) use ($officeIdsWithEnabledFeatureFlag) {
            return in_array($job->office->getId(), $officeIdsWithEnabledFeatureFlag);
        });
    }

    public static function inputDataProvider(): iterable
    {
        return [
            [null],
            [0],
            [1],
            [2],
        ];
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

    private function setMockFeatureFlagExpectations(bool $expectedValue = true): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->andReturn($expectedValue);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->officesByIdsQueryMock);
        unset($this->mockFeatureFlagService);
    }
}
