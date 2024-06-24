<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Contracts\Queries\GetRoutesByOfficeAndDateQuery;
use App\Domain\Scheduling\Entities\Route;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\RouteResolverTrait;
use App\Infrastructure\Repositories\PestRoutes\Translators\RouteCreation\PestRoutesRouteTranslator;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PestRoutesGetRoutesByOfficeAndDateQuery implements GetRoutesByOfficeAndDateQuery
{
    use RouteResolverTrait;

    public function __construct(
        private readonly PestRoutesRouteTranslator $translator,
        private readonly PestRoutesRoutesDataProcessor $routesDataProcessor,
    ) {
    }

    /**
     * Returns regular routes for specified office and date
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<Route>
     * @throws InternalServerErrorHttpException
     */
    public function get(Office $office, CarbonInterface $date): Collection
    {
        try {
            $pestRoutes = $this->getRegularRoutes($office, $date);

            return $pestRoutes->map(function ($pestRoute) use ($office) {
                return $this->translator->toDomain($pestRoute, $office);
            });
        } catch (NoRegularRoutesFoundException $e) {
            return collect();
        }
    }
}
