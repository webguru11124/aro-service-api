<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

class SetPreferredServicePro extends AbstractGeneralOptimizationRule
{
    /**
     * Rule to set preferred service pro for customer
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $isRuleApplied = false;

        /** @var Appointment $appointment */
        foreach ($optimizationState->getAllAppointments() as $appointment) {
            if (!$this->checkIfPreferredTechIdCanBeAddedAsSkill($optimizationState, $appointment->getPreferredTechId())) {
                continue;
            }

            $appointment->addSkillFromPreferredTech();
            $isRuleApplied = true;
        }

        return $isRuleApplied ? $this->buildSuccessExecutionResult() : $this->buildTriggeredExecutionResult();
    }

    private function checkIfPreferredTechIdCanBeAddedAsSkill(OptimizationState $optimizationState, int|null $preferredTechId): bool
    {
        if (is_null($preferredTechId)) {
            return false;
        }

        return $optimizationState->getRoutes()->contains(function ($route) use ($preferredTechId) {
            return $route->getServicePro()->getId() === $preferredTechId;
        });
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Set Preferred Service Pro';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule sets the preferred service pro of the customer as service pro.';
    }
}
