<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\PostOptimizationRules\MustHaveBlockedInsideSales;
use App\Domain\RouteOptimization\PostOptimizationRules\PostOptimizationRule;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Services\PestRoutes\Scopes\PestRoutesBlockedSpotReasons;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PestRoutesBlockedInsideSalesCaster extends AbstractPestRoutesPostOptimizationRuleCaster
{
    public const BLOCK_REASON = 'Blocked Inside Sales';
    private const PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG = 'isPestroutesSkipBuildEnabled';

    private OptimizationState $optimizationState;

    /** @var Collection<int, Collection<PestRoutesSpot>> */
    private Collection $groupedSpots;

    /** @var Collection<PestRoutesAppointment> */
    private Collection $appointments;

    // TODO: Remove skipBuild feature related code and conditions after PestRoute fix their bug with Appointment::spotId=0
    private bool $isSkipBuildEnabled = true;

    public function __construct(
        private readonly SpotsDataProcessor $spotsDataProcessor,
        private readonly AppointmentsDataProcessor $appointmentsDataProcessor,
        private readonly FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Blocks spots on route for the inside sales
     *
     * @param CarbonInterface $date
     * @param OptimizationState $optimizationState
     * @param MustHaveBlockedInsideSales $rule
     *
     * @return RuleExecutionResult
     */
    public function process(
        CarbonInterface $date,
        OptimizationState $optimizationState,
        PostOptimizationRule $rule
    ): RuleExecutionResult {
        $this->optimizationState = $optimizationState;

        $this->resolveEnabledFeatures();
        $this->resolveSpots();
        $this->resolveAppointments();
        $this->blockSpotsForInsideSales();

        return $this->buildSuccessExecutionResult($rule);
    }

    private function resolveSpots(): void
    {
        $routeIds = $this->getRouteIds();

        if (empty($routeIds)) {
            $this->groupedSpots = new Collection();

            return;
        }

        /** @var Collection<int, Collection<PestRoutesSpot>> $groupedSpots */
        $groupedSpots = $this->spotsDataProcessor->extract(
            $this->optimizationState->getOffice()->getId(),
            new SearchSpotsParams(
                officeIds: [$this->optimizationState->getOffice()->getId()],
                routeIds: $routeIds,
                skipBuild: $this->isSkipBuildEnabled,
            )
        )->groupBy('routeId');

        $this->groupedSpots = $groupedSpots;
    }

    private function resolveAppointments(): void
    {
        $routeIds = $this->getRouteIds();

        if (empty($routeIds)) {
            $this->appointments = new Collection();

            return;
        }

        $officeId = $this->optimizationState->getOffice()->getId();

        $this->appointments = $this->appointmentsDataProcessor->extract(
            $officeId,
            new SearchAppointmentsParams(
                officeIds: [$officeId],
                routeIds: $routeIds,
            )
        );
    }

    /**
     * @return int[]
     */
    private function getRouteIds(): array
    {
        return $this->optimizationState->getRoutes()
            ->filter(fn (Route $route) => $route->getServicePro()->getSkillsWithoutPersonal()->isNotEmpty())
            ->map(fn (Route $route) => $route->getId())
            ->toArray();
    }

    private function blockSpotsForInsideSales(): void
    {
        $spotsToBlock = new Collection();

        foreach ($this->optimizationState->getRoutes() as $route) {
            /** @var Collection<PestRoutesSpot> $routeSpots */
            $routeSpots = $this->groupedSpots->get($route->getId());

            if (is_null($routeSpots)) {
                continue;
            }

            $spotsToBlock = $spotsToBlock->merge(
                $this->getSpotsToBlock($routeSpots, $route->getConfig())
            );
        }

        $this->blockSpots($spotsToBlock);
    }

    private function blockSpots(Collection $spotsToBlock): void
    {
        if ($spotsToBlock->isEmpty()) {
            return;
        }

        $this->spotsDataProcessor->blockMultiple(
            $this->optimizationState->getOffice()->getId(),
            $spotsToBlock,
            self::BLOCK_REASON
        );
    }

    private function getAppointmentBySpotId(int $spotId): PestRoutesAppointment|null
    {
        return $this->appointments->first(fn (PestRoutesAppointment $appointment) => $appointment->spotId === $spotId);
    }

    private function getSpotsToBlock(Collection $routeSpots, RouteConfig $routeConfig): Collection
    {
        $spotsToBlock = new Collection();

        if ($routeConfig->getInsideSales() <= 0) {
            return $spotsToBlock;
        }

        $orderedSpots = $routeSpots
            ->sortByDesc('start')
            ->slice($routeConfig->getSummary(), $routeConfig->getInsideSales());

        /** @var Collection<PestRoutesSpot> $orderedSpots */
        foreach ($orderedSpots as $spot) {
            $hasAppointments = !empty($spot->appointmentIds) || !empty($this->getAppointmentBySpotId($spot->id));

            $allowedBlockingReason = $spot->capacity > 0 || is_null($spot->blockReason) || array_reduce(
                PestRoutesBlockedSpotReasons::INSIDE_SALES_MARKERS,
                function ($carry, $reason) use ($spot) {
                    return $carry || stripos($spot->blockReason, $reason) !== false;
                },
                false
            );

            if ($hasAppointments || !$allowedBlockingReason) {
                break;
            }

            $spotsToBlock->push($spot);
        }

        return $spotsToBlock;
    }

    private function resolveEnabledFeatures(): void
    {
        $this->isSkipBuildEnabled = $this->featureFlagService->isFeatureEnabledForOffice(
            $this->optimizationState->getOffice()->getId(),
            self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG
        );
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Blocked Inside Sales';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Blocks the spots for the inside sales';
    }
}
