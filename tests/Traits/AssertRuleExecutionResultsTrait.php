<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

/**
 * Adds assertion method that asserts rules has been triggered and applied to the optimizationState
 */
trait AssertRuleExecutionResultsTrait
{
    private function assertSuccessRuleResult(RuleExecutionResult $ruleExecutionResult): void
    {
        $this->assertTrue($ruleExecutionResult->isTriggered());
        $this->assertTrue($ruleExecutionResult->isApplied());
        $this->assertEquals((new \ReflectionClass($this->getClassRuleName()))->getShortName(), $ruleExecutionResult->getRuleId());
    }

    private function assertTriggeredRuleResult(RuleExecutionResult $ruleExecutionResult): void
    {
        $this->assertTrue($ruleExecutionResult->isTriggered());
        $this->assertFalse($ruleExecutionResult->isApplied());
        $this->assertEquals((new \ReflectionClass($this->getClassRuleName()))->getShortName(), $ruleExecutionResult->getRuleId());
    }

    private function assertSkippedRuleResult(RuleExecutionResult $ruleExecutionResult): void
    {
        $this->assertFalse($ruleExecutionResult->isTriggered());
        $this->assertFalse($ruleExecutionResult->isApplied());
        $this->assertEquals((new \ReflectionClass($this->getClassRuleName()))->getShortName(), $ruleExecutionResult->getRuleId());
    }

    abstract protected function getClassRuleName(): string;
}
