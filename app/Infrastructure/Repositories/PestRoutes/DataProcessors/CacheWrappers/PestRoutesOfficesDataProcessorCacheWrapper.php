<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesOfficesDataProcessor;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;

class PestRoutesOfficesDataProcessorCacheWrapper extends AbstractCachedWrapper implements OfficesDataProcessor
{
    private const CACHE_TTL = [
        'extract' => 2592000, //30 days
    ];
    private const CACHE_PREFIX = 'PestRoutesOffices_';

    public function __construct(PestRoutesOfficesDataProcessor $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchOfficesParams $params
     *
     * @return Collection<PestRoutesOffice>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchOfficesParams $params): Collection
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
