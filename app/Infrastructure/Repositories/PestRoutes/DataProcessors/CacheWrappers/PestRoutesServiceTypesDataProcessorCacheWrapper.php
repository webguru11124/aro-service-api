<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesServiceTypesDataProcessor;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Illuminate\Support\Collection;

class PestRoutesServiceTypesDataProcessorCacheWrapper extends AbstractCachedWrapper implements ServiceTypesDataProcessor
{
    private const CACHE_TTL = [
        'extract' => 2592000, //30 days
    ];
    private const CACHE_PREFIX = 'PestRoutesServiceTypes_';

    public function __construct(PestRoutesServiceTypesDataProcessor $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchServiceTypesParams $params
     *
     * @return Collection<PestRoutesServiceType>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchServiceTypesParams $params): Collection
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
