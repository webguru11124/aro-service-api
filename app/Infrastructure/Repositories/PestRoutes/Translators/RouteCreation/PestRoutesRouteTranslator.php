<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators\RouteCreation;

use App\Domain\Scheduling\Entities\Route;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Helpers\DateTimeHelper;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Carbon\Carbon;

class PestRoutesRouteTranslator
{
    /**
     * @param PestRoutesRoute $pestRoutesRoute
     * @param Office $office
     *
     * @return Route
     */
    public function toDomain(PestRoutesRoute $pestRoutesRoute, Office $office): Route
    {
        return new Route(
            id: $pestRoutesRoute->id,
            officeId: $pestRoutesRoute->officeId,
            date: Carbon::parse($pestRoutesRoute->date->format(DateTimeHelper::DATE_FORMAT), $office->getTimeZone()),
            templateId: $pestRoutesRoute->templateId,
            employeeId: $pestRoutesRoute->assignedTech,
        );
    }
}
