<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveRouteSummary;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustHaveRouteSummaryTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private MustHaveRouteSummary $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new MustHaveRouteSummary();
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $result = $this->rule->process($optimizationState);

        $this->assertSuccessRuleResult($result);
        /** @var Route $route */
        $route = $optimizationState->getRoutes()->first();
        $this->assertEquals(1, $route->getConfig()->getSummary());
    }

    protected function getClassRuleName(): string
    {
        return MustHaveRouteSummary::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
