<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\PostOptimizationRules\MustUpdateRouteSummary;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Average;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PestRoutesMustUpdateRouteSummaryCaster extends AbstractPestRoutesPostOptimizationRuleCaster
{
    private const FEATURE_FLAG = 'isDisplayOptimizationScoreInRouteSummaryEnabled';
    private const ROUTE_SUMMARY_WITH_SCORE = 'ARO Summary: Route Optimization Score: %d%%, Office Optimization Score: %d%%, As of: %s.';
    private const AS_OF_FORMAT = 'M d, h:iA T';

    private MustUpdateRouteSummary $rule;
    private OptimizationState $optimizationState;
    private bool $displayOptimizationScoreInSummary;

    /** @var Collection<Route> */
    private Collection $routes;

    /** @var Collection<int, Collection<PestRoutesSpot>> */
    private Collection $spots;

    public function __construct(
        private readonly SpotsDataProcessor $spotsDataProcessor,
        private readonly RouteStatisticsService $routeStatisticsService,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Adds route summary to the last spot
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     * @param MustUpdateRouteSummary $rule
     *
     * @return RuleExecutionResult
     */
    public function process(
        CarbonInterface $date,
        OptimizationState $optimizationState,
        PostOptimizationRule $rule
    ): RuleExecutionResult {
        $this->rule = $rule;
        $this->optimizationState = $optimizationState;

        $this->resolveEnabledFeatures();
        $this->resolveRoutes();
        $this->resolveSpots();
        $this->updateRoutesSummary();

        return $this->buildSuccessExecutionResult($rule);
    }

    private function resolveRoutes(): void
    {
        $this->routes = $this->optimizationState->getRoutes()->filter($this->rule->getRoutesFilter());
    }

    private function updateRoutesSummary(): void
    {
        $date = Carbon::now($this->optimizationState->getOffice()->getTimeZone());

        foreach ($this->routes as $route) {
            if ($route->getConfig()->getSummary() === 0) {
                continue;
            }

            $summary = $this->displayOptimizationScoreInSummary
                ? $this->getRouteSummaryWithScore($route, $date)
                : $this->getRouteSummaryWithStats($route, $date);
            $this->addRouteSummary($route, $summary);
        }
    }

    private function resolveEnabledFeatures(): void
    {
        $this->displayOptimizationScoreInSummary = $this->featureFlagService->isFeatureEnabledForOffice(
            $this->optimizationState->getOffice()->getId(),
            self::FEATURE_FLAG
        );
    }

    private function getRouteSummaryWithScore(Route $route, CarbonInterface $date): string
    {
        /** @var Average|null $optimizationScore */
        $optimizationScore = $this->optimizationState->getAverageScores()
            ->first(fn (Average $average) => $average->getKey() === MetricKey::OPTIMIZATION_SCORE);

        return sprintf(
            self::ROUTE_SUMMARY_WITH_SCORE,
            $route->getOptimizationScore()->value() * 100,
            (float) $optimizationScore?->getScore()->value() * 100,
            $date->format(self::AS_OF_FORMAT)
        );
    }

    private function getRouteSummaryWithStats(Route $route, CarbonInterface $date): string
    {
        return $this->rule->formatRouteSummary(
            $this->routeStatisticsService->getRouteSummary($route, $date)
        );
    }

    private function resolveSpots(): void
    {
        $officeId = $this->optimizationState->getOffice()->getId();
        $routeIds = $this->routes->map(fn (Route $route) => $route->getId())->toArray();

        if (empty($routeIds)) {
            $this->spots = new Collection();

            return;
        }

        /** @var Collection<int, Collection<PestRoutesSpot>> $groupedSpots */
        $groupedSpots = $this->spotsDataProcessor
            ->extract(
                $officeId,
                new SearchSpotsParams(
                    officeIds: [$officeId],
                    routeIds: $routeIds
                )
            )
            ->groupBy('routeId');

        $this->spots = $groupedSpots;
    }

    private function addRouteSummary(Route $route, string $routeSummary): void
    {
        $spots = $this->spots->get($route->getId());

        if (is_null($spots)) {
            return;
        }

        /** @var PestRoutesSpot $lastSpot */
        $lastSpot = $spots->sortBy('start')->last();

        $this->spotsDataProcessor->block($route->getOfficeId(), $lastSpot->id, $routeSummary);
    }
}
