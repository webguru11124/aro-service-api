<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveInsideSales;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustHaveInsideSalesTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private MustHaveInsideSales $rule;

    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);

        $this->rule = new MustHaveInsideSales($this->mockFeatureFlagService);

    }

    /**
     * @test
     */
    public function it_does_not_process_rule_when_feature_is_disabled(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnFalse();

        $result = $this->rule->process($optimizationState);

        $this->assertTriggeredRuleResult($result);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnTrue();

        $result = $this->rule->process($optimizationState);

        $this->assertSuccessRuleResult($result);
        /** @var Route $route */
        $route = $optimizationState->getRoutes()->first();
        $this->assertEquals(2, $route->getConfig()->getInsideSales());
    }

    protected function getClassRuleName(): string
    {
        return MustHaveInsideSales::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
        unset($this->mockFeatureFlagService);
    }
}
