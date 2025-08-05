<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Exceptions\UnknownRouteOptimizationEngineIdentifier;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomPlanModeTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\VroomToDomainTranslator;
use App\Infrastructure\Services\Vroom\VroomBusinessRulesCastService;
use App\Infrastructure\Services\Vroom\VroomRouteOptimizationService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RouteOptimizationServiceFactoryTest extends TestCase
{
    protected VroomToDomainTranslator|MockInterface $vroomToDomainTranslator;
    protected DomainToVroomTranslator|MockInterface $domainToVroomTranslator;
    protected DomainToVroomPlanModeTranslator|MockInterface $domainToVroomPlanModeTranslator;
    protected VroomBusinessRulesCastService|MockInterface $ruleCastService;
    protected VroomRouteOptimizationService|MockInterface $optimizationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vroomToDomainTranslator = Mockery::mock(VroomToDomainTranslator::class);
        $this->domainToVroomTranslator = app(DomainToVroomTranslator::class);
        $this->domainToVroomPlanModeTranslator = app(DomainToVroomPlanModeTranslator::class);
        $this->ruleCastService = Mockery::mock(VroomBusinessRulesCastService::class);

        $this->optimizationService = new VroomRouteOptimizationService(
            $this->vroomToDomainTranslator,
            $this->domainToVroomTranslator,
            $this->domainToVroomPlanModeTranslator,
            $this->ruleCastService
        );
    }

    /**
     * @test
     *
     * ::getRouteOptimizationService
     */
    public function it_returns_vroom_optimization_engine(): void
    {
        $factory = new RouteOptimizationServiceFactory(
            $this->optimizationService
        );

        $optimizationService = $factory->getRouteOptimizationService(OptimizationEngine::VROOM);

        $this->assertInstanceOf(VroomRouteOptimizationService::class, $optimizationService);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_engine_with_provided_identifier(): void
    {
        $factory = new RouteOptimizationServiceFactory(
            $this->optimizationService
        );

        $this->expectException(UnknownRouteOptimizationEngineIdentifier::class);
        $factory->getRouteOptimizationService(OptimizationEngine::GOOGLE);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset(
            $this->vroomToDomainTranslator,
            $this->domainToVroomTranslator,
            $this->domainToVroomPlanModeTranslator,
            $this->ruleCastService,
            $this->optimizationService
        );
    }
}
