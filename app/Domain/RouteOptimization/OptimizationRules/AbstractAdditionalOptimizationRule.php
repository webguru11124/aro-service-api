<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

abstract class AbstractAdditionalOptimizationRule implements AdditionalOptimizationRule
{
    /**
     * It creates a RuleExecutionResult with the result of the rule execution
     *
     * @param bool $isTriggered
     * @param bool $isApplied
     *
     * @return RuleExecutionResult
     */
    private function buildExecutionResult(bool $isTriggered = false, bool $isApplied = false): RuleExecutionResult
    {
        return new RuleExecutionResult(
            $this->id(),
            $this->name(),
            $this->description(),
            $isTriggered,
            $isApplied
        );
    }

    /**
     * It creates a RuleExecutionResult of triggered and applied rule
     *
     * @return RuleExecutionResult
     */
    protected function buildSuccessExecutionResult(): RuleExecutionResult
    {
        return $this->buildExecutionResult(true, true);
    }

    /**
     * It creates a RuleExecutionResult of triggered rule only
     *
     * @return RuleExecutionResult
     */
    protected function buildTriggeredExecutionResult(): RuleExecutionResult
    {
        return $this->buildExecutionResult(true);
    }

    /**
     * It creates a RuleExecutionResult of not triggered rule
     *
     * @param OptimizationState $optimizationState
     *
     * @return bool
     */
    protected function isSkipped(OptimizationState $optimizationState): bool
    {
        if ($optimizationState->getOptimizationParams()->isRuleDisabled($this->id())) {
            $optimizationState->addRuleExecutionResults(collect([
                $this->buildExecutionResult(),
            ]));

            return true;
        }

        return false;
    }

    /**
     * Returns class name without path as string
     *
     * @return string
     */
    public function id(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
