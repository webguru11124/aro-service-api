<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Jobs\OptimizeRoutesSimulationJob;
use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Exceptions\OptimizationStateNotFoundException;
use App\Domain\RouteOptimization\Services\OptimizationService;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\TestValue;

class OptimizeRoutesSimulationJobTest extends TestCase
{
    private OptimizeRoutesSimulationJob $job;
    private OptimizationState $optimizationState;

    private MockInterface|OptimizationService $mockOptimizationService;
    private MockInterface|OptimizationStateRepository $mockOptimizationStateRepository;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->setupMocks();

        $disabledRules = [];

        $this->job = new OptimizeRoutesSimulationJob(
            TestValue::OPTIMIZATION_STATE_ID,
            $disabledRules,
        );
        $this->optimizationState = OptimizationStateFactory::make([
            'id' => TestValue::OPTIMIZATION_STATE_ID,
            'unassignedAppointments' => [],
        ]);
    }

    private function setupMocks(): void
    {
        $this->mockOptimizationService = Mockery::mock(OptimizationService::class);
        $this->mockOptimizationStateRepository = Mockery::mock(OptimizationStateRepository::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_processes_optimization(): void
    {
        $this->mockOptimizationStateRepository
            ->shouldReceive('findById')
            ->once()
            ->with(TestValue::OPTIMIZATION_STATE_ID)
            ->andReturn($this->optimizationState);

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->with($this->optimizationState)
            ->andReturn($this->optimizationState);

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->once()
            ->withArgs(function (OptimizationState $state) {
                return $state->getId() === TestValue::OPTIMIZATION_STATE_ID;
            });

        Log::shouldReceive('info')->twice();

        $this->runJob();

        $this->assertTrue($this->optimizationState->getOptimizationParams()->simulationRun);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_skips_optimization_when_source_optimization_state_not_found(): void
    {
        $this->mockOptimizationStateRepository
            ->shouldReceive('findById')
            ->andThrow(OptimizationStateNotFoundException::instance(TestValue::OPTIMIZATION_STATE_ID));

        $this->mockOptimizationService
            ->shouldReceive('optimize')
            ->never();

        $this->mockOptimizationStateRepository
            ->shouldReceive('save')
            ->never();

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('notice')->once();

        $this->runJob();
    }

    /**
     * @test
     *
     * ::failed
     */
    public function it_logs_error_on_failure(): void
    {
        Log::shouldReceive('error')->once();

        $this->job->failed(new Exception('Test'));
    }

    private function runJob(): void
    {
        $this->job->handle(
            $this->mockOptimizationService,
            $this->mockOptimizationStateRepository,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->job);
        unset($this->mockOptimizationService);
        unset($this->mockOptimizationStateRepository);
        unset($this->optimizationState);
    }
}
