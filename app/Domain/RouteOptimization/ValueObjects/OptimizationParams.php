<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

readonly class OptimizationParams
{
    /**
     * @param bool $lastOptimizationRun
     * @param bool $simulationRun
     * @param bool $buildPlannedOptimization
     * @param string[] $disabledRules
     */
    public function __construct(
        public bool $lastOptimizationRun = false,
        public bool $simulationRun = false,
        public bool $buildPlannedOptimization = false,
        public array $disabledRules = [],
    ) {
    }

    /**
     * Returns true if rule is disabled
     *
     * @param string $ruleId
     *
     * @return bool
     */
    public function isRuleDisabled(string $ruleId): bool
    {
        return in_array($ruleId, $this->disabledRules);
    }
}
