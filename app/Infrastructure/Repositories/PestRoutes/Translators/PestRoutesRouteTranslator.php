<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Infrastructure\Helpers\DateTimeHelper;
use App\Infrastructure\Services\PestRoutes\Scopes\PestRoutesBlockedSpotReasons;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PestRoutesRouteTranslator
{
    public function __construct(
        private readonly PestRoutesServiceProTranslator $serviceProTranslator
    ) {
    }

    /**
     * @param PestRoutesRoute $pestRoutesRoute
     * @param PestRoutesEmployee|null $employee
     * @param Collection<int, PestRoutesSpot> $spotsCollection
     *
     * @return Route
     */
    public function toDomain(PestRoutesRoute $pestRoutesRoute, PestRoutesEmployee|null $employee, Collection $spotsCollection): Route
    {
        $servicePro = $this->serviceProTranslator->toDomain($pestRoutesRoute, $employee, $spotsCollection);
        $timeZone = $spotsCollection->first()?->start->getTimezone();

        return new Route(
            id: $pestRoutesRoute->id,
            officeId: $pestRoutesRoute->officeId,
            date: Carbon::parse($pestRoutesRoute->date->format(DateTimeHelper::DATE_FORMAT), $timeZone),
            servicePro: $servicePro,
            routeType: RouteType::fromString($pestRoutesRoute->title),
            actualCapacityCount: $spotsCollection->count(),
            config: new RouteConfig(insideSales: $this->getAmountOfInsideSalesSpots($spotsCollection)),
        );
    }

    private function getAmountOfInsideSalesSpots(Collection $spots): int
    {
        return $spots->filter(
            fn (PestRoutesSpot $spot) => $spot->capacity === 0 && !empty($spot->blockReason)
        )->filter(
            function (PestRoutesSpot $spot) {
                return array_reduce(
                    PestRoutesBlockedSpotReasons::INSIDE_SALES_MARKERS,
                    function ($carry, $marker) use ($spot) {
                        return $carry || stripos($spot->blockReason, $marker) !== false;
                    },
                    false
                );
            }
        )->count();
    }
}
