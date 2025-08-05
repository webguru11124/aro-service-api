<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RouteTemplatesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRouteTemplatesDataProcessor;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\Params\SearchRouteTemplatesParams;
use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplate as PestRoutesRouteTemplate;
use Illuminate\Support\Collection;

class PestRoutesRouteTemplatesDataProcessorCacheWrapper extends AbstractCachedWrapper implements RouteTemplatesDataProcessor
{
    private const CACHE_TTL = [
        'extract' => 2592000, //30 days
    ];
    private const CACHE_PREFIX = 'PestRoutesRouteTemplates_';

    public function __construct(PestRoutesRouteTemplatesDataProcessor $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchRouteTemplatesParams $params
     *
     * @return Collection<PestRoutesRouteTemplate>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchRouteTemplatesParams $params): Collection
    {
        return $this->cached(__FUNCTION__, $officeId, $params);
    }

    protected function getCacheTtl(string $methodName): int
    {
        return self::CACHE_TTL[$methodName];
    }

    protected function getPrefix(): string
    {
        return self::CACHE_PREFIX;
    }
}
