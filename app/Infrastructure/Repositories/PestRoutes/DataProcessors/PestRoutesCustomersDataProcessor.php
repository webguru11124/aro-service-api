<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\PestRoutesSDK\Resources\Customers\Params\UpdateCustomersParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Illuminate\Support\Collection;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CustomersDataProcessor;

class PestRoutesCustomersDataProcessor implements CustomersDataProcessor
{
    use PestRoutesClientAware;

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchCustomersParams $params
     *
     * @return Collection<PestRoutesCustomer>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchCustomersParams $params): Collection
    {
        $pestRoutesCustomers = $this->getClient()->office($officeId)
            ->customers()
            ->includeData()
            ->search($params)
            ->all();

        /** @var Collection<PestRoutesCustomer> $pestRoutesCustomers */
        $pestRoutesCustomers = new Collection($pestRoutesCustomers->items);

        return $pestRoutesCustomers;
    }

    /**
     * Update customer with given params
     *
     * @param int $officeId
     * @param UpdateCustomersParams $params
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     * @throws \JsonException
     */
    public function update(int $officeId, UpdateCustomersParams $params): bool
    {
        return (bool) $this->getClient()
            ->office($officeId)
            ->customers()
            ->update($params);
    }

    /**
     * Reset preferred tech for customer to default value 0
     *
     * @param int $officeId
     * @param int $customerId
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     * @throws \JsonException
     */
    public function resetPreferredTech(int $officeId, int $customerId): bool
    {
        return $this->update($officeId, new UpdateCustomersParams(
            customerId: $customerId,
            preferredTech: 0,
        ));
    }
}
