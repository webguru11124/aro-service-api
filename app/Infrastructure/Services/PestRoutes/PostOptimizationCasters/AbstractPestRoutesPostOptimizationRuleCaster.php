<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\PostOptimizationRuleCaster;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

abstract class AbstractPestRoutesPostOptimizationRuleCaster implements PostOptimizationRuleCaster
{
    /**
     * It creates a RuleExecutionResult with the result of the rule execution
     *
     * @param PostOptimizationRule $rule
     * @param bool $isTriggered
     * @param bool $isApplied
     *
     * @return RuleExecutionResult
     */
    private function buildExecutionResult(
        PostOptimizationRule $rule,
        bool $isTriggered = false,
        bool $isApplied = false
    ): RuleExecutionResult {
        return new RuleExecutionResult(
            $this->id($rule),
            $rule->name(),
            $rule->description(),
            $isTriggered,
            $isApplied
        );
    }

    /**
     * It creates a RuleExecutionResult of triggered and applied rule
     *
     * @param PostOptimizationRule $rule
     *
     * @return RuleExecutionResult
     */
    protected function buildSuccessExecutionResult(PostOptimizationRule $rule): RuleExecutionResult
    {
        return $this->buildExecutionResult($rule, true, true);
    }

    /**
     * It creates a RuleExecutionResult of triggered rule only
     *
     * @param PostOptimizationRule $rule
     *
     * @return RuleExecutionResult
     */
    protected function buildTriggeredExecutionResult(PostOptimizationRule $rule): RuleExecutionResult
    {
        return $this->buildExecutionResult($rule, true);
    }

    /**
     * Returns class name without path as string
     *
     * @param PostOptimizationRule $rule
     *
     * @return string
     */
    protected function id(PostOptimizationRule $rule): string
    {
        return (new \ReflectionClass($rule))->getShortName();
    }
}
