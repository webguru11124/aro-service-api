<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\BusinessRuleCasters;

use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Infrastructure\Services\Vroom\BusinessRuleCasters\TravelSpeedCaster;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomTranslator;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;

class TravelSpeedCasterTest extends TestCase
{
    private const SPEED_FACTOR_INCREASE_VALUE = 0.01;
    private const DEFAULT_SPEED_FACTOR = 1.00;

    private TravelSpeedCaster $travelSpeedCaster;

    private IncreaseTravelSpeed|MockInterface $mockIncreaseTravelSpeed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockIncreaseTravelSpeed = Mockery::mock(IncreaseTravelSpeed::class);
        $this->travelSpeedCaster = new TravelSpeedCaster();
    }

    /**
     * @test
     */
    public function it_casts_rule(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = TestOptimizationStateFactory::make();
        $dataTranslator = app(DomainToVroomTranslator::class);
        $vroomInputData = $dataTranslator->translate($optimizationState);

        /** @var Vehicle $vehicle */
        foreach ($vroomInputData->getVehicles() as $vehicle) {
            $vehicle->setSpeedFactor(self::DEFAULT_SPEED_FACTOR);
        }

        $this->mockIncreaseTravelSpeed
            ->shouldReceive('getSpeedFactorIncreaseValue')
            ->times($vroomInputData->getVehicles()->count())
            ->andReturn(self::SPEED_FACTOR_INCREASE_VALUE);

        $result = $this->travelSpeedCaster->cast(
            $vroomInputData,
            $optimizationState,
            $this->mockIncreaseTravelSpeed
        );

        $expectedSpeedFactor = self::DEFAULT_SPEED_FACTOR + self::SPEED_FACTOR_INCREASE_VALUE;
        /** @var Vehicle $vehicle */
        $vehicle = $result->getVehicles()->first();
        $this->assertEquals($expectedSpeedFactor, $vehicle->getSpeedFactor());

    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockIncreaseTravelSpeed);
        unset($this->mockOptimizationStateFactory);
        unset($this->travelSpeedCaster);
    }
}
