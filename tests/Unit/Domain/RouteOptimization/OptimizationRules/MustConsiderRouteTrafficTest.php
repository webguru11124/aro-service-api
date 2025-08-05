<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\OptimizationRules\MustConsiderRoadTraffic;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustConsiderRouteTrafficTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private MustConsiderRoadTraffic $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new MustConsiderRoadTraffic();
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $result = $this->rule->process($optimizationState);

        $this->assertTriggeredRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return MustConsiderRoadTraffic::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
