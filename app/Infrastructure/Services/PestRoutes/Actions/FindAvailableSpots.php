<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Actions;

use App\Infrastructure\Dto\FindAvailableSpotsDto;
use App\Infrastructure\Queries\PestRoutes\Params\RoutesCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\Params\SpotsCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\PestRoutesRoutesCachedQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesSpotsCachedQuery;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use App\Infrastructure\Services\PestRoutes\Entities\SpotFactory;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Illuminate\Support\Collection;

class FindAvailableSpots
{
    private const INITIAL_SKILL = 'INI';
    private const int CACHE_TTL = 60 * 3; // 3 min

    private FindAvailableSpotsDto $dto;

    /** @var Collection<PestRoutesRoute> */
    private Collection $routes;

    /** @var Collection<PestRoutesSpot> */
    private Collection $spots;

    public function __construct(
        private readonly PestRoutesSpotsCachedQuery $routeSpotsQuery,
        private readonly PestRoutesRoutesCachedQuery $routesQuery,
        private readonly SpotFactory $spotFactory,
    ) {
    }

    /**
     * @return Collection<Spot>
     */
    public function __invoke(FindAvailableSpotsDto $dto): Collection
    {
        $this->dto = $dto;

        return $this->process();
    }

    /**
     * @return Collection<Spot>
     */
    private function process(): Collection
    {
        $this->routes = $this->routesQuery
            ->cached(self::CACHE_TTL, $this->dto->skipCache)
            ->get(new RoutesCachedQueryParams(
                $this->dto->office->getId(),
                $this->dto->startDate,
                $this->dto->endDate
            ))
            ->keyBy('id');

        $this->spots = $this->routeSpotsQuery
            ->cached(self::CACHE_TTL, $this->dto->skipCache)
            ->get(new SpotsCachedQueryParams(
                $this->dto->office->getId(),
                $this->dto->startDate,
                $this->dto->endDate
            ));

        return $this->orderSpots(
            $this->limitSpots(
                $this->buildSpots()
            )
        );
    }

    /**
     * @return Collection<Spot>
     */
    private function buildSpots(): Collection
    {
        $allSpots = new Collection();

        $allPestRoutesSpots = $this->spots->filter(
            fn (PestRoutesSpot $pestRoutesSpot) => $pestRoutesSpot->currentAppointmentId === null
        );

        /** @var PestRoutesSpot $pestRoutesSpot */
        foreach ($allPestRoutesSpots as $pestRoutesSpot) {
            $pestRoutesRoute = $this->routes->get($pestRoutesSpot->routeId);

            if ($pestRoutesRoute === null) {
                continue;
            }

            $spot = $this->spotFactory->makeSpot($pestRoutesSpot, $pestRoutesRoute);

            if (!$this->spotMatchesCriteria($spot)) {
                continue;
            }

            $allSpots->add($spot);
        }

        return $allSpots;
    }

    private function spotMatchesCriteria(Spot $spot): bool
    {
        if ($spot->getType() === SpotType::BUCKET) {
            return true;
        }

        if ($this->aroSpotMatchesCriteria($spot)) {
            return true;
        }

        return false;
    }

    private function aroSpotMatchesCriteria(Spot $spot): bool
    {
        if ($spot->getType() !== SpotType::ARO_BLOCKED) {
            return false;
        }

        $closestDistance = $this->getClosestDistance($spot);
        if ($closestDistance === null || $closestDistance > $this->dto->distanceThreshold) {
            return false;
        }

        $spotSkills = $this->getSpotSkills($spot);
        if ($spotSkills === null) {
            return false;
        }

        if ($this->dto->isInitial && !in_array(self::INITIAL_SKILL, $spotSkills)) {
            return false;
        }

        return true;
    }

    private function getClosestDistance(Spot $spot): float|null
    {
        $from = $spot->getPreviousCoordinate();
        $to = $spot->getNextCoordinate();

        $fromDistance = $from?->distanceTo($this->dto->coordinate)->getMiles();
        $toDistance = $to?->distanceTo($this->dto->coordinate)->getMiles();

        return match (true) {
            $fromDistance === null && $toDistance === null => null,
            $fromDistance === null && $toDistance !== null => $toDistance,
            $fromDistance !== null && $toDistance === null => $fromDistance,
            default => min($fromDistance, $toDistance)
        };
    }

    /**
     * @param Spot $spot
     *
     * @return array<string>|null
     */
    private function getSpotSkills(Spot $spot): array|null
    {
        $blockReason = substr($spot->getBlockReason(), 4);
        $blockReason = json_decode($blockReason);

        return empty($blockReason->skills) ? null : $blockReason->skills;
    }

    /**
     * @param Collection<Spot> $spots
     *
     * @return Collection<Spot>
     */
    private function limitSpots(Collection $spots): Collection
    {
        if ($this->dto->responseLimit === null) {
            return $spots;
        }

        $groupedSpots = $spots->groupBy(
            fn (Spot $spot) => $spot->getDate()->toDateString() . $spot->getWindow()
        );
        $limitedSpots = new Collection();

        foreach ($groupedSpots as $spotsGroup) {
            $spotsSlice = $spotsGroup->slice(0, $this->dto->responseLimit);
            $limitedSpots = $limitedSpots->merge($spotsSlice);
        }

        return $limitedSpots;
    }

    /**
     * @param Collection<Spot> $spots
     *
     * @return Collection<Spot>
     */
    private function orderSpots(Collection $spots): Collection
    {
        return $spots->sortBy([
            fn (Spot $left, Spot $right) => $left->getDate()->timestamp <=> $right->getDate()->timestamp,
            fn (Spot $left, Spot $right) => $left->getWindow() <=> $right->getWindow(),
        ])->values();
    }
}
