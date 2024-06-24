<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Factories\OptimizationStateFactory;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;

class OptimizationStateFactoryTest extends TestCase
{
    private OptimizationStateFactory $factory;
    private OptimizationStateRepository|MockInterface $optimizationStateRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optimizationStateRepositoryMock = Mockery::mock(OptimizationStateRepository::class);

        $this->factory = new OptimizationStateFactory(
            $this->optimizationStateRepositoryMock,
        );
    }

    /**
     * @test
     */
    public function it_creates_an_optimization_state_from_an_optimized_state(): void
    {
        $this->optimizationStateRepositoryMock
            ->shouldReceive('getNextId')
            ->once()
            ->andReturn(random_int(100, 200));

        $route = RouteFactory::make([
            'capacity' => 20,
            'officeId' => TestValue::OFFICE_ID,
        ]);

        /** @var OptimizationState $startingState */
        $startingState = TestOptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
        ]);

        $resultingState = $this->factory->makeFromOptimizationState(
            $startingState,
            OptimizationEngine::VROOM,
            OptimizationStatus::POST,
        );

        $this->assertInstanceOf(OptimizationState::class, $resultingState);
        $this->assertEquals($startingState->getOffice(), $resultingState->getOffice());
        $this->assertEquals($startingState->getOptimizationTimeFrame(), $resultingState->getOptimizationTimeFrame());
        $this->assertEquals($startingState->getRoutes(), $resultingState->getRoutes());
        $this->assertNotSame($startingState->getRoutes(), $resultingState->getRoutes());
        $this->assertEquals($startingState->getUnassignedAppointments(), $resultingState->getUnassignedAppointments());
        $this->assertNotSame($startingState->getUnassignedAppointments(), $resultingState->getUnassignedAppointments());

        /** @var Route $sourceRoute */
        $sourceRoute = $startingState->getRoutes()->first();
        /** @var Route $resultRoute */
        $resultRoute = $resultingState->getRoutes()->first();
        $this->assertEquals($sourceRoute, $resultRoute);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->factory);
        unset($this->optimizationStateRepositoryMock);
    }
}
