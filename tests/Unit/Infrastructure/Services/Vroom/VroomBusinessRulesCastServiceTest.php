<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom;

use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Infrastructure\Services\Vroom\BusinessRuleCasters\TravelSpeedCaster;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomTranslator;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use App\Infrastructure\Services\Vroom\VroomBusinessRulesCastService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;

class VroomBusinessRulesCastServiceTest extends TestCase
{
    private VroomBusinessRulesCastService $castService;
    private VroomInputData $vroomInputData;
    private OptimizationState $optimizationState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInputData();
        $this->castService = new VroomBusinessRulesCastService();
    }

    private function setupInputData(): void
    {
        $dataTranslator = app(DomainToVroomTranslator::class);

        $this->optimizationState = TestOptimizationStateFactory::make(['rules' => []]);
        $this->vroomInputData = $dataTranslator->translate($this->optimizationState);
    }

    /**
     * @test
     */
    public function it_casts_rules(): void
    {
        $ruleCaster = Mockery::mock(TravelSpeedCaster::class);
        $this->instance(TravelSpeedCaster::class, $ruleCaster);

        $rule = new IncreaseTravelSpeed();

        $ruleCaster->shouldReceive('cast')
            ->once()
            ->with($this->vroomInputData, $this->optimizationState, $rule)
            ->andReturn($this->vroomInputData);

        Log::shouldReceive('info')->once();

        $this->castService->castRules(
            $this->vroomInputData,
            $this->optimizationState,
            new Collection([$rule])
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->castService);
        unset($this->vroomInputData);
    }
}
