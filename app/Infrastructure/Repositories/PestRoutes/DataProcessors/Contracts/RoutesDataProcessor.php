<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\UpdateRoutesParams;
use Illuminate\Support\Collection;

interface RoutesDataProcessor
{
    /**
     * Extract routes based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchRoutesParams $params
     *
     * @return Collection
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchRoutesParams $params): Collection;

    /**
     * Create a new route based on the office ID and creation parameters.
     *
     * @param int $officeId
     * @param CreateRoutesParams $createRoutesParams
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function create(int $officeId, CreateRoutesParams $createRoutesParams): int;

    /**
     * Update a route based on the office ID and update parameters.
     *
     * @param int $officeId
     * @param UpdateRoutesParams $updateRoutesParams
     *
     * @return int
     * @throws InternalServerErrorHttpException
     */
    public function update(int $officeId, UpdateRoutesParams $updateRoutesParams): int;

    /**
     * Delete a route based on the office ID and route ID.
     *
     * @param int $officeId
     * @param int $routeId
     *
     * @return bool
     */
    public function delete(int $officeId, int $routeId): bool;
}
