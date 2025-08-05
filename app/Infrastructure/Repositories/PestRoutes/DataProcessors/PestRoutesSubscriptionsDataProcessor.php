<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription as PestRoutesSubscription;
use Illuminate\Support\Collection;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SubscriptionsDataProcessor;

class PestRoutesSubscriptionsDataProcessor implements SubscriptionsDataProcessor
{
    use PestRoutesClientAware;

    /**
     * @param int $officeId
     * @param SearchSubscriptionsParams|AbstractHttpParams $params
     *
     * @return array<int>
     */
    public function extractIds(int $officeId, AbstractHttpParams|SearchSubscriptionsParams $params): array
    {
        $pestRoutesSubscriptions = $this->getClient()->office($officeId)
            ->subscriptions()
            ->search($params);

        return $pestRoutesSubscriptions->ids();
    }

    /**
     * @param int $officeId
     * @param int[] $subscriptionIds
     *
     * @return Collection<PestRoutesSubscription>
     */
    public function extractByIds(int $officeId, array $subscriptionIds): Collection
    {
        /** @var Collection<PestRoutesSubscription> $subscriptions */
        $subscriptions = collect(
            $this->getClient()->office($officeId)
                ->subscriptions()
                ->getByIds($subscriptionIds)
                ->items
        );

        return $subscriptions;
    }

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchSubscriptionsParams $params
     *
     * @return Collection<PestRoutesSubscription>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchSubscriptionsParams $params): Collection
    {
        $pestRoutesSubscriptions = $this->getClient()->office($officeId)
            ->subscriptions()
            ->includeData()
            ->search($params)
            ->all();

        /** @var Collection<PestRoutesSubscription> $pestRoutesSubscriptions */
        $pestRoutesSubscriptions = new Collection($pestRoutesSubscriptions->items);

        return $pestRoutesSubscriptions->values();
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
        return (bool) $this->getClient()
            ->office($officeId)
            ->subscriptions()
            ->update($params);
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
        return $this->update($officeId, new UpdateSubscriptionsParams(
            subscriptionId: $subscriptionId,
            customerId: $customerId,
            preferredEmployeeId: 0,
        ));
    }
}
