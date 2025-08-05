<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Events\OptimizationJob\OptimizationJobEnded;
use App\Application\Events\OptimizationJob\OptimizationJobFailed;
use App\Application\Events\OptimizationJob\OptimizationJobFinished;
use App\Application\Events\OptimizationJob\OptimizationJobStarted;
use App\Application\Events\OptimizationState\OptimizationStateStored;
use App\Application\Events\OptimizationState\OptimizationStateUpdated;
use App\Application\Jobs\OptimizeRoutesJob;
use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\Contracts\Services\OptimizationPostProcessService;
use App\Domain\Contracts\Services\WeatherService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Services\OptimizationService;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoAppointmentsFoundException;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Exceptions\RoutesHaveNoCapacityException;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStatePersister;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\WeatherInfoFactory;
use Tests\Tools\TestValue;

class OptimizeRoutesJobTest extends TestCase
{
    private Carbon $date;
    private OptimizeRoutesJob $job;
    private OptimizationState $optimizationState;
    private OptimizationState $plannedOptimizationState;

    private MockInterface|PestRoutesOptimizationStateResolver $mockOptimizationStateResolver;
    private MockInterface|PestRoutesOptimizationStatePersister $mockOptimizationStatePersister;
    private MockInterface|OptimizationService $mockOptimizationService;
    private MockInterface|OptimizationPostProcessService $mockOptimizationPostProcessService;
    private MockInterface|OptimizationStateRepository $mockOptimizationStateRepository;
    private MockInterface|WeatherService $mockWeatherService;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->setupMocks();

        $optimizationParams = new OptimizationParams(
            true,
            false,
            true,
        );
        $this->date = Carbon::tomorrow();
        $this->job = new OptimizeRoutesJob($this->date, OfficeFactory::make(['id' => TestValue::OFFICE_ID]), $optimizationParams);
        $this->optimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => [],
            'optimizationParams' => $optimizationParams,
        ]);
        $this->plannedOptimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => [],
            'status' => OptimizationStatus::PLAN,
            'optimizationParams' => $optimizationParams,
        ]);
    }

    private function setupMocks(): void
    {
        $this->mockOptimizationStateResolver = Mockery::mock(PestRoutesOptimizationStateResolver::class);
        $this->mockOptimizationStatePersister = Mockery::mock(PestRoutesOptimizationStatePersister::class);
        $this->mockOptimizationService = Mockery::mock(OptimizationService::class);
        $this->mockOptimizationPostProcessService = Mockery::mock(OptimizationPostProcessService::class);
        $this->mockOptimizationStateRepository = Mockery::mock(OptimizationStateRepository::class);
        $this->mockWeatherService = Mockery::mock(WeatherService::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_processes_optimization(): void
    {
        Config::set('app.debug', false);

        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                fn (Carbon $date, Office $office) => $date->toDateString() === $this->date->toDateString()
                    && $office->getId() === TestValue::OFFICE_ID
            )
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->with($this->optimizationState)
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('plan')
            ->once()
            ->with($this->optimizationState)
            ->andReturn($this->plannedOptimizationState);

        $this->mockOptimizationStatePersister
            ->shouldReceive('persist')
            ->once()
            ->with($this->optimizationState);

        $this->mockOptimizationPostProcessService
            ->shouldReceive('execute')
            ->once()
            ->withArgs(
                fn (Carbon $date, OptimizationState $optimizationState) => $date->toDateString() === $this->date->toDateString()
                    && $optimizationState === $this->optimizationState
                    && $optimizationState->isLastOptimizationRun()
            );

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->twice()
            ->with($this->optimizationState);

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->once()
            ->with($this->plannedOptimizationState);

        $this->runJob();

        Event::assertDispatched(OptimizationJobStarted::class);
        Event::assertDispatched(OptimizationJobEnded::class);
        Event::assertDispatched(OptimizationJobFinished::class);
        Event::assertDispatchedTimes(OptimizationStateStored::class, 3);
        Event::assertNotDispatched(OptimizationStateUpdated::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_does_not_create_planned_optimization_when_it_disabled(): void
    {
        Config::set('app.debug', false);
        $this->optimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => [],
            'optimizationParams' => new OptimizationParams(
                false,
                false,
                false,
            ),
        ]);

        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockWeatherService
            ->shouldReceive('getCurrentWeatherByCoordinates')
            ->once()
            ->andReturn(WeatherInfoFactory::make());

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('plan')
            ->never();

        $this->mockOptimizationStatePersister
            ->shouldReceive('persist')
            ->once()
            ->with($this->optimizationState);

        $this->mockOptimizationPostProcessService
            ->shouldReceive('execute')
            ->once();

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->twice()
            ->with($this->optimizationState);

        Log::shouldReceive('notice')->once();

        $this->runJob();
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_does_not_create_planned_optimization_when_failed(): void
    {
        Config::set('app.debug', false);
        $this->optimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => [],
            'optimizationParams' => new OptimizationParams(
                false,
                false,
                true,
            ),
        ]);

        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockWeatherService
            ->shouldReceive('getCurrentWeatherByCoordinates')
            ->once()
            ->andReturn(WeatherInfoFactory::make());

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('plan')
            ->with($this->optimizationState)
            ->andReturn($this->optimizationState);

        $this->mockOptimizationStatePersister
            ->shouldReceive('persist')
            ->once()
            ->with($this->optimizationState);

        $this->mockOptimizationPostProcessService
            ->shouldReceive('execute')
            ->once();

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->twice()
            ->with($this->optimizationState);

        Log::shouldReceive('notice')->once();

        $this->runJob();
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_does_not_persist_optimization_state_on_simulation_run(): void
    {
        Config::set('app.debug', false);
        $this->optimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => [],
            'optimizationParams' => new OptimizationParams(
                false,
                true,
                false,
            ),
        ]);

        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockWeatherService
            ->shouldReceive('getCurrentWeatherByCoordinates')
            ->once()
            ->andReturn(WeatherInfoFactory::make());

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('plan')
            ->never();

        $this->mockOptimizationStatePersister
            ->shouldReceive('persist')
            ->never();

        $this->mockOptimizationPostProcessService
            ->shouldReceive('execute')
            ->never();

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->twice()
            ->with($this->optimizationState);

        Log::shouldReceive('notice')->twice();

        $this->runJob();
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_processes_optimization_and_logs_debug_info(): void
    {
        Config::set('app.debug', true);

        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('plan')
            ->once()
            ->with($this->optimizationState)
            ->andReturn($this->plannedOptimizationState);

        $this->mockOptimizationStatePersister
            ->shouldReceive('persist')
            ->once()
            ->with($this->optimizationState);

        $this->mockOptimizationPostProcessService
            ->shouldReceive('execute')
            ->once();

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->twice()
            ->with($this->optimizationState);

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->once()
            ->with($this->plannedOptimizationState);

        $this->runJob();

        Event::assertDispatched(OptimizationJobStarted::class);
        Event::assertDispatched(OptimizationJobEnded::class);
        Event::assertDispatched(OptimizationJobFinished::class);
        Event::assertNotDispatched(OptimizationStateUpdated::class);
    }

    /**
     * @test
     *
     * @dataProvider exceptionProvider
     *
     * ::handle
     */
    public function it_handles_exception_and_logs_notice(): void
    {
        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andThrow(new NoRegularRoutesFoundException('Test exception'));

        Log::shouldReceive('notice')->once();

        $this->runJob();

        Event::assertNotDispatched(OptimizationStateUpdated::class);
    }

    /**
     * Data provider for the test.
     *
     * @return array
     */
    public static function exceptionProvider(): array
    {
        return [
            [NoRegularRoutesFoundException::class, 'Test exception for NoRegularRoutesFoundException'],
            [NoServiceProFoundException::class, 'Test exception for NoServiceProFoundException'],
            [NoAppointmentsFoundException::class, 'Test exception for NoAppointmentsFoundException'],
        ];
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_handles_no_capacity_exception_and_logs_notice(): void
    {
        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andThrow(new RoutesHaveNoCapacityException('Test exception'));

        Log::shouldReceive('notice')->once();

        $this->runJob();

        Event::assertNotDispatched(OptimizationStateUpdated::class);
    }

    /**
    * @test
    *
    * ::handle
    */
    public function it_correctly_handles_weather_info_exception_and_logs_warning(): void
    {
        $this->mockOptimizationStateResolver
            ->shouldReceive('resolve')
            ->once()
            ->andReturn($this->optimizationState);

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->twice()
            ->with($this->optimizationState);

        $this->mockWeatherService
            ->shouldReceive('getCurrentWeatherByCoordinates')
            ->andThrow(new \Exception('Weather service is unavailable'));

        $this->mockOptimizationService
            ->shouldReceive('plan')
            ->once()
            ->with($this->optimizationState)
            ->andReturn($this->plannedOptimizationState);

        $this->mockOptimizationStatePersister
            ->shouldReceive('persist')
            ->once()
            ->with($this->optimizationState);

        $this->mockOptimizationPostProcessService
            ->shouldReceive('execute')
            ->once();

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->with($this->optimizationState)
            ->andReturn($this->optimizationState);

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->once()
            ->with($this->plannedOptimizationState);

        Log::shouldReceive('warning')->once();

        $this->runJob();
    }

    /**
     * @test
     *
     * ::failed
     */
    public function it_dispatches_event_on_failure(): void
    {
        $this->job->failed(new Exception('Test'));

        Event::assertDispatched(OptimizationJobFailed::class);
        Event::assertDispatched(OptimizationJobFinished::class);
        Event::assertNotDispatched(OptimizationStateUpdated::class);
    }

    private function runJob(): void
    {
        $this->job->handle(
            $this->mockOptimizationStateResolver,
            $this->mockOptimizationStatePersister,
            $this->mockOptimizationService,
            $this->mockOptimizationPostProcessService,
            $this->mockOptimizationStateRepository,
            $this->mockWeatherService,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->job);
        unset($this->mockOptimizationService);
        unset($this->mockOptimizationStateResolver);
        unset($this->mockOptimizationStatePersister);
        unset($this->mockOptimizationPostProcessService);
        unset($this->mockOptimizationStateRepository);
        unset($this->optimizationState);
        unset($this->plannedOptimizationState);
    }
}
