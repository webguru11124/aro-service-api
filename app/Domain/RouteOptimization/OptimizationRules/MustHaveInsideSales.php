<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;

class MustHaveInsideSales extends AbstractGeneralOptimizationRule
{
    private const FEATURE_FLAG = 'isMandatoryInsideSalesEnabled';

    public function __construct(
        private readonly FeatureFlagService $featureFlagService
    ) {
    }

    /**
     * Rule to set number of mandatory inside sales spots to be on routes
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        if (!$this->isFeatureEnabled($optimizationState->getOffice()->getId())) {
            return $this->buildTriggeredExecutionResult();
        }

        $optimizationState->getRoutes()->each(
            fn (Route $route) => $route->setNumberOfInsideSales(
                DomainContext::getBlockedInsideSalesCount($route->getRouteType())
            )
        );

        return $this->buildSuccessExecutionResult();
    }

    private function isFeatureEnabled(int $officeId): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $officeId,
            self::FEATURE_FLAG
        );
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Inside Sales';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule adds mandatory inside sales spots to the routes.';
    }
}
