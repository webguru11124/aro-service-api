<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use App\Domain\RouteOptimization\ValueObjects\RouteGroupType;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

trait RouteResolverTrait
{
    public function __construct(
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
    ) {
    }

    /**
     * @param Office $office
     * @param CarbonInterface $date
     * @param bool|null $includeLocked
     *
     * @return Collection<PestRoutesRoute>
     * @throws InternalServerErrorHttpException
     * @throws NoRegularRoutesFoundException
     */
    private function getRegularRoutes(Office $office, CarbonInterface $date, bool|null $includeLocked = null): Collection
    {
        $params = new SearchRoutesParams(
            officeIds: [$office->getId()],
            dateStart: $date->clone()->startOfDay(),
            dateEnd: $date->clone()->endOfDay(),
            lockedRoute: $includeLocked
        );
        $allRoutes = $this->routesDataProcessor->extract($office->getId(), $params);

        $routes = $allRoutes->filter(
            fn (PestRoutesRoute $route) => $this->isMatchingRegularRouteTitle($route->groupTitle)
        );

        $routes = $routes->filter(
            fn (PestRoutesRoute $route) => RouteType::fromString($route->title) !== RouteType::UNKNOWN
        );

        if ($routes->isEmpty()) {
            throw NoRegularRoutesFoundException::instance($office->getId(), $office->getName(), $date);
        }

        return $routes;
    }

    /**
     * Checks if regular route title matches, accounting for pluralization.
     *
     * @param string $title
     *
     * @return bool
     */
    private function isMatchingRegularRouteTitle(string $title): bool
    {
        return RouteGroupType::fromString($title) === RouteGroupType::REGULAR_ROUTE;
    }
}
