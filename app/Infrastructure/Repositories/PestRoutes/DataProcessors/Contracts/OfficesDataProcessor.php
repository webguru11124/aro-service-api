<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;

interface OfficesDataProcessor
{
    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchOfficesParams $params
     *
     * @return Collection<PestRoutesOffice>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchOfficesParams $params): Collection;
}
