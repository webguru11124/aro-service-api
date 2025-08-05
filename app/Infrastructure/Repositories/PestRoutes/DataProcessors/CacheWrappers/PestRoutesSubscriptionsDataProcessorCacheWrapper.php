<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SubscriptionsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesSubscriptionsDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription as PestRoutesSubscription;
use Illuminate\Support\Collection;

class PestRoutesSubscriptionsDataProcessorCacheWrapper extends AbstractCachedWrapper implements SubscriptionsDataProcessor
{
    private const CACHE_TTL = [
        'extractIds' => 3600, // 1 hour
        'extractByIds' => 3600, // 1 hour
        'extract' => 3600, // 1 hour
    ];
    private const CACHE_PREFIX = 'PestRoutesSubscription_';

    public function __construct(PestRoutesSubscriptionsDataProcessor $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @param int $officeId
     * @param SearchSubscriptionsParams|AbstractHttpParams $params
     *
     * @return array<int>
     */
    public function extractIds(int $officeId, AbstractHttpParams|SearchSubscriptionsParams $params): array
    {
        return $this->cached(__FUNCTION__, $officeId, $params);
    }

    /**
     * @param int $officeId
     * @param int[] $subscriptionIds
     *
     * @return Collection<PestRoutesSubscription>
     */
    public function extractByIds(int $officeId, array $subscriptionIds): Collection
    {
        sort($subscriptionIds);

        return $this->cached(__FUNCTION__, $officeId, $subscriptionIds);
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchSubscriptionsParams $params
     *
     * @return Collection<PestRoutesSubscription>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchSubscriptionsParams $params): Collection
    {
        return $this->cached(__FUNCTION__, $officeId, $params);
    }

    /**
     * @param int $officeId
     * @param UpdateSubscriptionsParams $params
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function update(int $officeId, UpdateSubscriptionsParams $params): bool
    {
        return $this->wrapped->update($officeId, $params);
    }

    /**
     * Reset preferred tech for a subscription to default value 0
     *
     * @param int $officeId
     * @param int $subscriptionId
     * @param int $customerId
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function resetPreferredTech(int $officeId, int $subscriptionId, int $customerId): bool
    {
        return $this->wrapped->resetPreferredTech($officeId, $subscriptionId, $customerId);
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
