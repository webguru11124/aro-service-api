<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\UpdateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Illuminate\Support\Collection;

class PestRoutesRoutesDataProcessor implements RoutesDataProcessor
{
    use PestRoutesClientAware;

    /**
     * Extract routes based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchRoutesParams $params
     *
     * @return Collection<PestRoutesRoute>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchRoutesParams $params): Collection
    {
        $pestRoutesRoutes = $this->getClient()->office($officeId)
            ->routes()
            ->includeData()
            ->search($params)
            ->all();

        /** @var Collection<PestRoutesRoute> $pestRoutesRoutes */
        $pestRoutesRoutes = new Collection($pestRoutesRoutes->items);

        return $pestRoutesRoutes;
    }

    /**
     * Create a new route based on the office ID and creation parameters.
     *
     * @param int $officeId
     * @param CreateRoutesParams $createRoutesParams
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function create(int $officeId, CreateRoutesParams $createRoutesParams): int
    {
        return $this->getClient()
            ->office($officeId)
            ->routes()
            ->create($createRoutesParams);
    }

    /**
     * Update a route based on the office ID and update parameters.
     *
     * @param int $officeId
     * @param UpdateRoutesParams $updateRoutesParams
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function update(int $officeId, UpdateRoutesParams $updateRoutesParams): int
    {
        return $this->getClient()
            ->office($officeId)
            ->routes()
            ->update($updateRoutesParams);
    }

    /**
     * Delete a route based on the office ID and route ID.
     *
     * @param int $officeId
     * @param int $routeId
     *
     * @return bool
     */
    public function delete(int $officeId, int $routeId): bool
    {
        return $this->getClient()
            ->office($officeId)
            ->routes()
            ->delete($routeId);
    }
}
