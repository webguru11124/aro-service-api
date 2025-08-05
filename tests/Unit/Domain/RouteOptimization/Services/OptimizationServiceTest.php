<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Services;

use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeedForUnderutilizedRoutes;
use App\Domain\RouteOptimization\BusinessRulesRegister;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Domain\RouteOptimization\PostOptimizationHandlers\PostOptimizationHandler;
use App\Domain\RouteOptimization\PostOptimizationHandlers\PostOptimizationHandlersRegister;
use App\Domain\RouteOptimization\Services\OptimizationService;
use App\Domain\RouteOptimization\Services\RouteOptimizationScoreCalculationService;
use Exception;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;

class OptimizationServiceTest extends TestCase
{
    private OptimizationService $service;
    private OptimizationState $optimizationState;

    private MockInterface|BusinessRulesRegister $mockBusinessRulesRegister;
    private MockInterface|PostOptimizationHandlersRegister $mockPostOptimizationHandlersRegister;
    private MockInterface|RouteOptimizationService $mockRouteOptimizationService;
    private MockInterface|RouteOptimizationServiceFactory $mockRouteOptimizationServiceFactory;
    private MockInterface|RouteOptimizationScoreCalculationService $mockScoreCalculationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockBusinessRulesRegister = Mockery::mock(BusinessRulesRegister::class);
        $this->mockRouteOptimizationService = Mockery::mock(RouteOptimizationService::class);
        $this->mockRouteOptimizationServiceFactory = Mockery::mock(RouteOptimizationServiceFactory::class);
        $this->mockScoreCalculationService = Mockery::mock(RouteOptimizationScoreCalculationService::class);

        $this->mockPostOptimizationHandlersRegister = Mockery::mock(PostOptimizationHandlersRegister::class);
        $this->mockRouteOptimizationServiceFactory->shouldReceive('getRouteOptimizationService')
            ->andReturn($this->mockRouteOptimizationService);

        $this->service = new OptimizationService(
            $this->mockBusinessRulesRegister,
            $this->mockRouteOptimizationServiceFactory,
            $this->mockPostOptimizationHandlersRegister,
            $this->mockScoreCalculationService,
        );
        $this->optimizationState = OptimizationStateFactory::make(['unassignedAppointments' => []]);
    }

    /**
     * @test
     *
     * ::optimize
     */
    public function it_processes_optimization(): void
    {
        $optimizationState = $this->optimizationState;

        $registeredRules = new Collection();

        $this->mockRouteOptimizationService
            ->shouldReceive('optimize')
            ->once()
            ->withArgs(
                function ($passedState, $passedRules) use ($optimizationState, $registeredRules) {
                    return $passedState === $optimizationState && $passedRules->diff($registeredRules)->isEmpty();
                }
            )
            ->andReturn($optimizationState);

        $this->mockBusinessRulesRegister
            ->shouldReceive('getGeneralOptimizationRules')
            ->once()
            ->andReturn(new Collection());
        $this->mockBusinessRulesRegister
            ->shouldReceive('getAdditionalOptimizationRules')
            ->never();
        $this->mockPostOptimizationHandlersRegister
            ->shouldReceive('getHandlers')
            ->once()
            ->andReturn(new Collection());
        $this->mockScoreCalculationService->shouldReceive('calculate')
            ->once()
            ->andReturn($optimizationState);

        $resultOptimizationState = $this->service->optimize($optimizationState);

        $this->assertEmpty($resultOptimizationState->getRuleExecutionResults());
    }

    /**
     * @test
     *
     * ::optimize
     */
    public function it_performs_additional_optimization_runs(): void
    {
        $resultOptimizationState = OptimizationStateFactory::make([
            // unassigned appointments in result OptimizationState force additional optimization runs
            'unassignedAppointments' => AppointmentFactory::many(2),
        ]);

        $optimizeCallCount = 0;
        $optimizationState = $this->optimizationState;

        $this->mockRouteOptimizationService
            ->shouldReceive('optimize')
            ->times(3)
            ->withArgs(
                function ($passedState, $passedRules) use (&$optimizeCallCount, $optimizationState) {
                    $optimizeCallCount++;
                    $expectedRules = $this->getExpectedRulesByCallCount($optimizeCallCount);

                    return $passedState === $optimizationState && $this->containsRules($passedRules, $expectedRules);
                }
            )
            ->andReturn($resultOptimizationState);

        $this->mockBusinessRulesRegister
            ->shouldReceive('getGeneralOptimizationRules')
            ->once()
            ->andReturn(new Collection());
        $this->mockBusinessRulesRegister
            ->shouldReceive('getAdditionalOptimizationRules')
            ->twice()
            ->andReturn(new Collection());
        $this->mockPostOptimizationHandlersRegister
            ->shouldReceive('getHandlers')
            ->once()
            ->andReturn(new Collection());
        $this->mockScoreCalculationService->shouldReceive('calculate')
            ->once()
            ->andReturn($optimizationState);

        $resultOptimizationState = $this->service->optimize($optimizationState);

        $ruleExecutedResults = $resultOptimizationState->getRuleExecutionResults();
        $this->assertCount(4, $ruleExecutedResults);
    }

    /**
     * @test
     */
    public function it_processes_post_optimization_handling(): void
    {
        $optimizationState = $this->optimizationState;

        $this->mockRouteOptimizationService
            ->shouldReceive('optimize')
            ->andReturn($optimizationState);

        $this->mockBusinessRulesRegister
            ->shouldReceive('getGeneralOptimizationRules')
            ->once()
            ->andReturn(new Collection());
        $this->mockBusinessRulesRegister
            ->shouldReceive('getAdditionalOptimizationRules')
            ->never();

        $handler1 = Mockery::mock(PostOptimizationHandler::class);
        $handler1->shouldReceive('process')
            ->with($optimizationState)
            ->once()
            ->andReturnNull();

        $handler2 = Mockery::mock(PostOptimizationHandler::class);
        $handler2->shouldReceive('process')
            ->with($optimizationState)
            ->once()
            ->andReturnNull();

        $handlers = new Collection([
            $handler1,
            $handler2,
        ]);

        $this->mockPostOptimizationHandlersRegister
            ->shouldReceive('getHandlers')
            ->once()
            ->andReturn($handlers);
        $this->mockScoreCalculationService->shouldReceive('calculate')
            ->once()
            ->andReturn($optimizationState);

        $resultOptimizationState = $this->service->optimize($optimizationState);

        $this->assertEmpty($resultOptimizationState->getRuleExecutionResults());
    }

    private function containsRules(Collection $collection, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (!$collection->contains(fn ($value) => $value instanceof $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @test
     *
     * ::plan
     */
    public function it_plans_optimization(): void
    {
        $optimizationState = $this->optimizationState;

        $this->mockRouteOptimizationService
            ->shouldReceive('plan')
            ->once()
            ->withArgs(
                function ($passedState) use ($optimizationState) {
                    return $passedState === $optimizationState;
                }
            )
            ->andReturn($optimizationState);
        $this->mockBusinessRulesRegister
            ->shouldReceive('getGeneralPlanRules')
            ->once()
            ->andReturn(new Collection());

        $this->service->plan($optimizationState);
    }

    /**
     * @test
     *
     * ::plan
     */
    public function it_returns_source_optimization_state_on_exception(): void
    {
        $optimizationState = $this->optimizationState;

        $this->mockRouteOptimizationService
            ->shouldReceive('plan')
            ->andThrow(new Exception('Test exception'));
        $this->mockBusinessRulesRegister
            ->shouldReceive('getGeneralPlanRules')
            ->once()
            ->andReturn(new Collection());

        $result = $this->service->plan($optimizationState);
        $this->assertEquals($result, $optimizationState);
    }

    private function getExpectedRulesByCallCount(int $callCount): array
    {
        return match ($callCount) {
            2 => [
                IncreaseTravelSpeed::class,
                IncreaseTravelSpeedForUnderutilizedRoutes::class,
            ],
            3 => [
                IncreaseTravelSpeed::class,
                IncreaseTravelSpeed::class,
                IncreaseTravelSpeedForUnderutilizedRoutes::class,
                IncreaseTravelSpeedForUnderutilizedRoutes::class,
            ],
            default => [],
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->job);
        unset($this->mockRouteOptimizationService);
        unset($this->mockBusinessRulesRegister);
        unset($this->mockPostOptimizationHandlersRegister);
        unset($this->mockRouteOptimizationServiceFactory);
        unset($this->mockScoreCalculationService);
    }
}
