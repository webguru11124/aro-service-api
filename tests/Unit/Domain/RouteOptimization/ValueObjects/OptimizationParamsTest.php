<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use Tests\TestCase;

class OptimizationParamsTest extends TestCase
{
    /**
     * @test
     */
    public function create_optimization_params(): void
    {
        $params = new OptimizationParams(
            true,
            true,
            true,
            ['rule1', 'rule2']
        );

        $this->assertTrue($params->lastOptimizationRun);
        $this->assertTrue($params->simulationRun);
        $this->assertTrue($params->buildPlannedOptimization);
        $this->assertEquals(['rule1', 'rule2'], $params->disabledRules);
    }

    /**
     * @test
     *
     * ::isRuleDisabled
     */
    public function it_returns_true_when_rule_is_disabled(): void
    {
        $params = new OptimizationParams(
            disabledRules: ['rule1']
        );

        $this->assertTrue($params->isRuleDisabled('rule1'));
        $this->assertFalse($params->isRuleDisabled('rule2'));
    }
}
