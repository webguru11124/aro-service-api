<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Services;

use App\Domain\Contracts\OptimizationRule;
use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeed;
use App\Domain\RouteOptimization\BusinessRules\IncreaseTravelSpeedForUnderutilizedRoutes;
use App\Domain\RouteOptimization\BusinessRulesRegister;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Exceptions\InvalidTotalWeightOfMetricsException;
use App\Domain\RouteOptimization\Exceptions\UnknownRouteOptimizationEngineIdentifier;
use App\Domain\RouteOptimization\Factories\RouteOptimizationServiceFactory;
use App\Domain\RouteOptimization\OptimizationRules\AdditionalOptimizationRule;
use App\Domain\RouteOptimization\OptimizationRules\GeneralOptimizationRule;
use App\Domain\RouteOptimization\PostOptimizationHandlers\PostOptimizationHandlersRegister;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use Illuminate\Support\Collection;

class OptimizationService
{
    private const MAX_OPTIMIZATION_ATTEMPTS = 3;

    private OptimizationState $sourceOptimizationState;
    private OptimizationState $resultOptimizationState;
    private Collection $rules;

    public function __construct(
        private readonly BusinessRulesRegister $businessRulesRegister,
        private readonly RouteOptimizationServiceFactory $routeOptimizationServiceFactory,
        private readonly PostOptimizationHandlersRegister $postOptimizationHandlersRegister,
        private readonly RouteOptimizationScoreCalculationService $scoreCalculationService,
    ) {
    }

    /**
     * Performs the routes optimization according to business rules
     *
     * @param OptimizationState $optimizationState
     *
     * @return OptimizationState
     * @throws InvalidTotalWeightOfMetricsException
     * @throws UnknownRouteOptimizationEngineIdentifier
     */
    public function optimize(OptimizationState $optimizationState): OptimizationState
    {
        $this->sourceOptimizationState = $optimizationState;

        $this->rules = collect();
        $this->applyGeneralOptimizationRules();
        $this->runOptimization();

        $optimizationAttempt = 1;

        while (
            !$this->isOptimizationRequirementsFulfilled()
            && $optimizationAttempt < self::MAX_OPTIMIZATION_ATTEMPTS
        ) {
            $this->applyAdditionalOptimizationRules();
            $this->rules->push(new IncreaseTravelSpeed(), new IncreaseTravelSpeedForUnderutilizedRoutes());
            $this->runOptimization();
            $optimizationAttempt++;
        }

        $this->makePostOptimizationAdjustments();
        $this->calculateRouteOptimizationScore();
        $this->setAllTriggeredRules();

        return $this->resultOptimizationState;
    }

    private function setAllTriggeredRules(): void
    {
        $ruleExecutionResults = $this->rules->map(function (OptimizationRule $rule) {
            return new RuleExecutionResult(
                $rule->id(),
                $rule->name(),
                $rule->description(),
                true,
                true
            );
        });

        $this->resultOptimizationState->addRuleExecutionResults($ruleExecutionResults);
    }

    /**
     * @throws UnknownRouteOptimizationEngineIdentifier
     */
    private function runOptimization(): void
    {
        $this->resultOptimizationState = $this->getRouteOptimizationService($this->sourceOptimizationState)
            ->optimize($this->sourceOptimizationState, $this->rules);
    }

    /**
     * Returns optimization service by optimization engine identifier
     *
     * @param OptimizationState $sourceOptimizationState
     *
     * @return RouteOptimizationService
     * @throws UnknownRouteOptimizationEngineIdentifier
     */
    private function getRouteOptimizationService(OptimizationState $sourceOptimizationState): RouteOptimizationService
    {
        return $this->routeOptimizationServiceFactory->getRouteOptimizationService(
            $sourceOptimizationState->getOptimizationEngine()
        );
    }

    private function isOptimizationRequirementsFulfilled(): bool
    {
        return $this->resultOptimizationState->getUnassignedAppointments()->isEmpty();
    }

    private function applyGeneralOptimizationRules(): void
    {
        foreach ($this->businessRulesRegister->getGeneralOptimizationRules() as $rule) {
            /** @var GeneralOptimizationRule $rule */
            $this->sourceOptimizationState->applyRule($rule);
        }
    }

    private function applyAdditionalOptimizationRules(): void
    {
        foreach ($this->businessRulesRegister->getAdditionalOptimizationRules() as $rule) {
            /** @var AdditionalOptimizationRule $rule */
            $rule->process($this->sourceOptimizationState, $this->resultOptimizationState);
        }
    }

    private function makePostOptimizationAdjustments(): void
    {
        foreach ($this->postOptimizationHandlersRegister->getHandlers() as $handler) {
            $handler->process($this->resultOptimizationState);
        }
    }

    /**
     * @throws InvalidTotalWeightOfMetricsException
     */
    private function calculateRouteOptimizationScore(): void
    {
        $this->resultOptimizationState = $this->scoreCalculationService->calculate($this->resultOptimizationState);
    }

    /**
     * Plans OptimizationState routing data such as drive time and distance
     *
     * @param OptimizationState $sourceOptimizationState
     *
     * @return OptimizationState
     */
    public function plan(OptimizationState $sourceOptimizationState): OptimizationState
    {
        try {
            $this->applyGeneralPlanRules($sourceOptimizationState);

            return $this->getRouteOptimizationService($sourceOptimizationState)->plan($sourceOptimizationState);
        } catch (\Throwable $e) {
            return $sourceOptimizationState;
        }
    }

    private function applyGeneralPlanRules(OptimizationState $sourceOptimizationState): void
    {
        foreach ($this->businessRulesRegister->getGeneralPlanRules() as $rule) {
            /** @var GeneralOptimizationRule $rule */
            $sourceOptimizationState->applyRule($rule);
        }
    }
}
