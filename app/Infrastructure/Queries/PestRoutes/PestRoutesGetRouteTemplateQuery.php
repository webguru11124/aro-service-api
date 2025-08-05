<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Contracts\Queries\GetRouteTemplateQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\RouteTemplatesNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesRouteTemplatesDataProcessorCacheWrapper;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\Params\SearchRouteTemplatesParams;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplate as PestRoutesRouteTemplate;

class PestRoutesGetRouteTemplateQuery implements GetRouteTemplateQuery
{
    private const APPLICABLE_TEMPLATE_NAME = 'Regular Routes';

    public function __construct(
        private readonly PestRoutesRouteTemplatesDataProcessorCacheWrapper $routeTemplatesDataProcessor,
    ) {
    }

    /**
     * Get applicable route template id for specified office.
     *
     * @param Office $office
     *
     * @return int
     * @throws RouteTemplatesNotFoundException
     */
    public function get(Office $office): int
    {
        $officeRouteTemplates = $this->routeTemplatesDataProcessor->extract(
            $office->getId(),
            new SearchRouteTemplatesParams(
                officeIds: [$office->getId()],
            )
        );

        if ($officeRouteTemplates->isEmpty()) {
            throw RouteTemplatesNotFoundException::instance($office->getId(), $office->getName(), now());
        }

        $applicationRouteTemplate = $officeRouteTemplates->first(
            fn (PestRoutesRouteTemplate $routeTemplate) => $routeTemplate->templateName === self::APPLICABLE_TEMPLATE_NAME
        );

        if ($applicationRouteTemplate === null) {
            throw RouteTemplatesNotFoundException::applicableTemplateNotFound($office->getId(), $office->getName(), now());
        }

        return $applicationRouteTemplate->id;
    }
}
