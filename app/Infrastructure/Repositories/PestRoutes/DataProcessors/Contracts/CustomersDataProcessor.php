<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Illuminate\Support\Collection;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\UpdateCustomersParams;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;

interface CustomersDataProcessor
{
    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchCustomersParams $params
     *
     * @return Collection<PestRoutesCustomer>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchCustomersParams $params): Collection;

    /**
     * @param int $officeId
     * @param UpdateCustomersParams $params
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     * @throws \JsonException
     */
    public function update(int $officeId, UpdateCustomersParams $params): bool;

    /**
     * @param int $officeId
     * @param int $customerId
     *
     * @return bool
     * @throws InternalServerErrorHttpException
     * @throws \JsonException
     */
    public function resetPreferredTech(int $officeId, int $customerId): bool;
}
