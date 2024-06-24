<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\Params\SearchRouteTemplatesParams;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplate as PestRoutesRouteTemplate;
use Illuminate\Support\Collection;

interface RouteTemplatesDataProcessor
{
    /**
     * Extract route templates based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchRouteTemplatesParams $params
     *
     * @return Collection<PestRoutesRouteTemplate>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchRouteTemplatesParams $params): Collection;
}
