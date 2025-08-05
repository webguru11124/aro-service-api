<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom;

use App\Application\Events\Vroom\VroomRequestFailed;
use App\Application\Events\Vroom\VroomRequestSent;
use App\Application\Events\Vroom\VroomResponseReceived;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Infrastructure\Exceptions\VroomErrorResponseException;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomPlanModeTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\VroomToDomainTranslator;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use App\Infrastructure\Services\Vroom\VroomBusinessRulesCastService;
use App\Infrastructure\Services\Vroom\VroomRouteOptimizationService;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;

class VroomRouteOptimizationServiceTest extends TestCase
{
    private VroomToDomainTranslator $mockVroomToDomainTranslator;
    private DomainToVroomTranslator $domainToVroomTranslator;
    private DomainToVroomPlanModeTranslator $domainToVroomPlanModeTranslator;
    private VroomBusinessRulesCastService $mockRuleCastService;
    private VroomRouteOptimizationService $service;
    private VroomInputData $vroomInputData;
    private OptimizationState $optimizationState;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('vroom.connection.url', 'https://test-url.test');
        //Set retries to 1 to keep test performant
        Config::set('vroom.connection.retries', 1);

        $this->mockVroomToDomainTranslator = Mockery::mock(VroomToDomainTranslator::class);
        $this->domainToVroomTranslator = app(DomainToVroomTranslator::class);
        $this->domainToVroomPlanModeTranslator = app(DomainToVroomPlanModeTranslator::class);
        $this->mockRuleCastService = Mockery::mock(VroomBusinessRulesCastService::class);

        $this->service = new VroomRouteOptimizationService(
            $this->mockVroomToDomainTranslator,
            $this->domainToVroomTranslator,
            $this->domainToVroomPlanModeTranslator,
            $this->mockRuleCastService
        );
        $this->optimizationState = TestOptimizationStateFactory::make();

        Event::fake();
    }

    /**
     * @test
     *
     * ::optimize
     */
    public function it_optimizes_routes_successfully(): void
    {
        $rules = new Collection();

        $vroomOutput = ['optimized' => 'data'];
        $expectedOptimizedState = Mockery::mock(OptimizationState::class);

        $this->vroomInputData = $this->domainToVroomTranslator->translate($this->optimizationState);

        $this->mockRuleCastService
            ->shouldReceive('castRules')
            ->once()
            ->andReturn($this->vroomInputData);

        $this->mockVroomToDomainTranslator
            ->shouldReceive('translate')
            ->once()
            ->with($vroomOutput, $this->optimizationState, OptimizationStatus::POST)
            ->andReturn($expectedOptimizedState);

        Http::fake([
            '*' => Http::response($vroomOutput),
        ]);

        $result = $this->service->optimize($this->optimizationState, $rules);

        $this->assertSame($expectedOptimizedState, $result);
        Event::assertDispatched(VroomRequestSent::class);
        Event::assertDispatched(VroomResponseReceived::class);
    }

    /**
     * @test
     *
     * ::plan
     */
    public function it_plans_routes_successfully(): void
    {
        $vroomOutput = ['planned' => 'data'];
        $expectedOptimizedState = Mockery::mock(OptimizationState::class);

        $this->vroomInputData = $this->domainToVroomPlanModeTranslator->translate($this->optimizationState);

        $this->mockRuleCastService
            ->shouldReceive('castRules')
            ->never();

        $this->mockVroomToDomainTranslator
            ->shouldReceive('translate')
            ->once()
            ->withArgs(function ($vroomOutput, OptimizationState $optimizationState, OptimizationStatus $status) {

                return $optimizationState == $this->optimizationState
                    && !empty($vroomOutput)
                    && $status === OptimizationStatus::PLAN;
            })
            ->andReturn($expectedOptimizedState);

        Http::fake([
            '*' => Http::response($vroomOutput),
        ]);

        $result = $this->service->plan($this->optimizationState);

        $this->assertSame($expectedOptimizedState, $result);
        Event::assertDispatched(VroomRequestSent::class);
        Event::assertDispatched(VroomResponseReceived::class);
    }

    /**
     * @test
     */
    public function it_throws_exception_for_vroom_error_response(): void
    {
        $rules = new Collection();

        $this->vroomInputData = $this->domainToVroomTranslator->translate($this->optimizationState);
        $this->mockRuleCastService->shouldReceive('castRules')->andReturn($this->vroomInputData);

        Http::fake([
            '*' => Http::response([], HttpStatus::INTERNAL_SERVER_ERROR),
        ]);

        $this->expectException(VroomErrorResponseException::class);
        $this->service->optimize($this->optimizationState, $rules);
        Event::assertDispatched(VroomRequestFailed::class);
    }

    /**
     * @test
     *
     * ::getIdentifier
     */
    public function it_returns_correct_optimization_engine_identifier(): void
    {
        $this->assertSame(OptimizationEngine::VROOM, $this->service->getIdentifier());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->service);
        unset($this->vroomInputData);
        unset($this->optimizationState);
        unset($this->mockVroomToDomainTranslator);
        unset($this->domainToVroomTranslator);
        unset($this->domainToVroomPlanModeTranslator);
        unset($this->mockRuleCastService);
    }
}
