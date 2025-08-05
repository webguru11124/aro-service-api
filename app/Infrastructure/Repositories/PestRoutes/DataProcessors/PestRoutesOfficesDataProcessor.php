<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;

class PestRoutesOfficesDataProcessor implements OfficesDataProcessor
{
    use PestRoutesClientAware;

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchOfficesParams $params
     *
     * @return Collection<PestRoutesOffice>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchOfficesParams $params): Collection
    {
        $pestRoutesOffices = $this->getClient()
            ->office($officeId)
            ->includeData()
            ->search($params)
            ->all();

        /** @var Collection<PestRoutesOffice> $pestRoutesOffices */
        $pestRoutesOffices = new Collection($pestRoutesOffices->items);

        return $pestRoutesOffices;
    }
}
