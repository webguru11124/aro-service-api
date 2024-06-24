<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Managers;

use App\Application\DTO\RouteOptimizationDTO;
use App\Application\Events\OptimizationSkipped;
use App\Application\Jobs\OptimizeRoutesJob;
use App\Application\Managers\RoutesOptimizationManager;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\Office\OfficesByIdsQuery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class RoutesOptimizationManagerTest extends TestCase
{
    private const QUEUE_NAME = 'TestQueue';
    private const ALLOW_ROUTE_OPTIMIZATION_BEFORE_TIME = '07:30';
    private const FEATURE_FLAG = 'isDayBeforeServiceOptimizationEnabled';
    private const OPTIMIZATION_ALLOWED_FEATURE_FLAG = 'isRouteOptimizationForOfficeEnabled';

    private MockInterface|OfficesByIdsQuery $officesByIdsQueryMock;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    private RoutesOptimizationManager $routesOptimizationManager;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.debug', false);
        Config::set('queue.queues.route_optimization', self::QUEUE_NAME);
        Config::set('aptive.allow_route_optimization_before_time', self::ALLOW_ROUTE_OPTIMIZATION_BEFORE_TIME);
        Queue::fake();

        $this->setupMockOfficeRepository();
        $this->setupMockFeatureFlagService();
        $this->routesOptimizationManager = new RoutesOptimizationManager(
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
     * @param Carbon $date
     * @param int[] $officeIds
     *
     * @return void
     */
    public function it_dispatches_optimization_job(Carbon $date, array $officeIds): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->with(...$officeIds)
            ->once()
            ->andReturn($officeCollection);

        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, $date);
        $expectedJobsCount = count($officeIds);

        $this->routesOptimizationManager->manage($optimizationData);

        Queue::assertPushed(OptimizeRoutesJob::class, $expectedJobsCount);
        Queue::assertPushedOn(self::QUEUE_NAME, OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_dispatches_optimization_job_twice_when_number_of_days_to_optimize_provided(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->with(...$officeIds)
            ->once()
            ->andReturn($officeCollection);

        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, Carbon::tomorrow(), 0, 1);

        $this->routesOptimizationManager->manage($optimizationData);

        Queue::assertPushed(OptimizeRoutesJob::class, 2);
    }

    /**
     * @test
     */
    public function it_dispatches_optimization_job_for_today_when_start_date_not_provided(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);
        $this->setMockOfficeRepositoryExpectations($officeCollection);

        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, null);

        $this->routesOptimizationManager->manage($optimizationData);

        Queue::assertPushed(OptimizeRoutesJob::class, function (OptimizeRoutesJob $job) {
            return $job->date->toDateString() == Carbon::today()->toDateString();
        });
    }

    /**
     * @test
     */
    public function it_dispatches_optimization_job_next_day_when_num_day_after_start_date_provided(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(6, 0, 0, TestValue::TZ));

        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);
        $this->setMockOfficeRepositoryExpectations($officeCollection);

        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, null, 2);

        $this->routesOptimizationManager->manage($optimizationData);

        Queue::assertPushed(OptimizeRoutesJob::class, function (OptimizeRoutesJob $job) {
            return $job->date->toDateString() == Carbon::today()->addDays(2)->toDateString();
        });
    }

    /**
     * @test
     */
    public function it_dispatches_optimization_job_for_tomorrow(): void
    {
        $startDate = Carbon::createFromTime(20, 0, 0, TestValue::TZ);
        Carbon::setTestNow($startDate);

        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->with(...$officeIds)
            ->once()
            ->andReturn($officeCollection);

        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, null, 1);
        $expectedJobsCount = count($officeIds);

        $this->routesOptimizationManager->manage($optimizationData);

        Queue::assertPushed(OptimizeRoutesJob::class, $expectedJobsCount);
        Queue::assertPushedOn(self::QUEUE_NAME, OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_logs_notice_when_office_local_time_is_behind_allowed_time_for_optimization(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, TestValue::TZ));

        $officeIds = [TestValue::OFFICE_ID];
        $date = Carbon::today();
        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->setMockOfficeRepositoryExpectations($officeCollection);
        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, $date);
        Log::shouldReceive('notice')->once();
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(function ($event) {
                return $event instanceof OptimizationSkipped;
            });

        $this->routesOptimizationManager->manage($optimizationData);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_skips_allowed_time_validation_when_debug_is_enabled(): void
    {
        Config::set('app.debug', true);

        Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, TestValue::TZ));

        $officeIds = [TestValue::OFFICE_ID];
        $date = Carbon::tomorrow();
        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->setMockOfficeRepositoryExpectations($officeCollection);
        $this->setMockFeatureFlagExpectations();
        Log::shouldReceive('notice')->never();
        Log::shouldReceive('info')->once();

        $optimizationData = new RouteOptimizationDTO($officeIds, $date);

        $this->routesOptimizationManager->manage($optimizationData);
        Queue::assertPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     *
     * @dataProvider dayBeforeServiceDataProvider
     */
    public function it_does_not_run_optimization_before_the_day_of_the_service_when_feature_is_turned_off(
        Carbon $date
    ): void {
        Config::set('app.debug', true);

        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);
        $this->setMockOfficeRepositoryExpectations($officeCollection);

        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->twice()
            ->withSomeOfArgs(TestValue::OFFICE_ID)
            ->andReturn(true, false);

        Log::shouldReceive('notice')->once();

        $optimizationData = new RouteOptimizationDTO($officeIds, $date);

        $this->routesOptimizationManager->manage($optimizationData);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_does_not_run_optimization_for_the_office_when_feature_is_turned_off(): void
    {
        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);
        $this->setMockOfficeRepositoryExpectations($officeCollection);

        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->withSomeOfArgs(self::OPTIMIZATION_ALLOWED_FEATURE_FLAG)
            ->andReturn(false);

        Log::shouldReceive('notice')->once();

        $optimizationData = new RouteOptimizationDTO($officeIds, Carbon::yesterday()->setTime(0, 0));

        $this->routesOptimizationManager->manage($optimizationData);
        Queue::assertNotPushed(OptimizeRoutesJob::class);
    }

    public static function dayBeforeServiceDataProvider(): iterable
    {
        yield [
            Carbon::now(TestValue::TZ),
        ];
        yield [
            Carbon::tomorrow(TestValue::TZ),
        ];
    }

    /**
     * @test
     */
    public function it_evaluates_local_office_time_properly(): void
    {
        Config::set('app.debug', true);
        Carbon::setTestNow(Carbon::today()->setTimezone('UTC')->setTimeFromTimeString('01:00:00'));
        $date = Carbon::tomorrow()->setTimezone('UTC');

        $officeIds = [TestValue::OFFICE_ID];
        $officeCollection = $this->getOfficeCollection($officeIds);
        $this->setMockOfficeRepositoryExpectations($officeCollection);

        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->never()
            ->withSomeOfArgs(self::FEATURE_FLAG);

        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->withSomeOfArgs(self::OPTIMIZATION_ALLOWED_FEATURE_FLAG)
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        $optimizationData = new RouteOptimizationDTO($officeIds, $date);

        $this->routesOptimizationManager->manage($optimizationData);
        Queue::assertPushed(OptimizeRoutesJob::class);
    }

    /**
     * @test
     */
    public function it_does_not_throw_exception_if_date_is_not_today(): void
    {
        Carbon::setTestNow(Carbon::createFromTime(8, 0, 0, TestValue::TZ));

        $dateTwoDaysAhead = Carbon::today()->addDays(2);

        $officeIds = [1, 2, 3];
        $officeCollection = $this->getOfficeCollection($officeIds);

        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->with(...$officeIds)
            ->once()
            ->andReturn($officeCollection);

        $this->setMockFeatureFlagExpectations();

        $optimizationData = new RouteOptimizationDTO($officeIds, $dateTwoDaysAhead);

        $this->routesOptimizationManager->manage($optimizationData);
    }

    private function setMockOfficeRepositoryExpectations(Collection $officeCollection): void
    {
        $this->officesByIdsQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn($officeCollection);
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

    private function setMockFeatureFlagExpectations(): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->andReturn(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->officesByIdsQueryMock);
        unset($this->mockFeatureFlagService);
    }
}
