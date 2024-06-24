<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RouteTemplatesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;

use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplate as PestRoutesRouteTemplate;

use Aptive\PestRoutesSDK\Resources\RouteTemplates\Params\SearchRouteTemplatesParams;
use Illuminate\Support\Collection;

class PestRoutesRouteTemplatesDataProcessor implements RouteTemplatesDataProcessor
{
    use PestRoutesClientAware;

    /**
     * Extract route templates based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchRouteTemplatesParams $params
     *
     * @return Collection<PestRoutesRouteTemplate>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchRouteTemplatesParams $params): Collection
    {
        $pestRoutesRouteTemplates = $this->getClient()->office($officeId)
            ->routeTemplates()
            ->includeData()
            ->search($params)
            ->all();

        return new Collection($pestRoutesRouteTemplates->items);
    }
}
