<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Illuminate\Support\Collection;

interface ServiceTypesDataProcessor
{
    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchServiceTypesParams $params
     *
     * @return Collection<PestRoutesServiceType>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchServiceTypesParams $params): Collection;
}
