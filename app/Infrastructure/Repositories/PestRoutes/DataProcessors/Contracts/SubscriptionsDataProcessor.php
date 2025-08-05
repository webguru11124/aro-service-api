<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Illuminate\Support\Collection;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Subscription as PestRoutesSubscription;

interface SubscriptionsDataProcessor
{
    /**
     * @param int $officeId
     * @param SearchSubscriptionsParams|AbstractHttpParams $params
     *
     * @return array<int>
     */
    public function extractIds(int $officeId, AbstractHttpParams|SearchSubscriptionsParams $params): array;

    /**
     * @param int $officeId
     * @param int[] $subscriptionIds
     *
     * @return Collection<PestRoutesSubscription>
     */
    public function extractByIds(int $officeId, array $subscriptionIds): Collection;

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchSubscriptionsParams $params
     *
     * @return Collection<PestRoutesSubscription>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchSubscriptionsParams $params): Collection;

    /**
     * @param int $officeId
     * @param UpdateSubscriptionsParams $params
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function update(int $officeId, UpdateSubscriptionsParams $params): bool;

    /**
     * @param int $officeId
     * @param int $subscriptionId
     * @param int $customerId
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     */
    public function resetPreferredTech(int $officeId, int $subscriptionId, int $customerId): bool;
}
