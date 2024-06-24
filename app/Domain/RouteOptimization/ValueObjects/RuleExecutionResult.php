<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

class RuleExecutionResult
{
    public function __construct(
        private readonly string $ruleId,
        private readonly string $ruleName,
        private readonly string $ruleDescription,
        private readonly bool $triggered = false,
        private readonly bool $applied = false,
    ) {
    }

    /**
     * @return string
     */
    public function getRuleId(): string
    {
        return $this->ruleId;
    }

    /**
     * @return string
     */
    public function getRuleName(): string
    {
        return $this->ruleName;
    }

    /**
     * @return string
     */
    public function getRuleDescription(): string
    {
        return $this->ruleDescription;
    }

    /**
     * @return bool
     */
    public function isTriggered(): bool
    {
        return $this->triggered;
    }

    /**
     * @return bool
     */
    public function isApplied(): bool
    {
        return $this->applied;
    }
}
