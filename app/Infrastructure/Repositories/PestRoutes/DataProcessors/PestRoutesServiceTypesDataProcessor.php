<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Illuminate\Support\Collection;

class PestRoutesServiceTypesDataProcessor implements ServiceTypesDataProcessor
{
    use PestRoutesClientAware;

    private const MUTUAL_OFFICE_ID = -1;

    private const PARAMS_DEFAULTS = [
        'typeIDs' => [],
        'officeIDs' => [],
        'description' => null,
        'category' => null,
        'reservice' => null,
    ];

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchServiceTypesParams $params
     *
     * @return Collection<PestRoutesServiceType>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchServiceTypesParams $params): Collection
    {
        $pestRoutesServiceTypes = $this->getClient()->office($officeId)
            ->serviceTypes()
            ->includeData()
            ->search($this->handleParams($params))
            ->all();

        /** @var Collection<PestRoutesServiceType> $pestRoutesServiceTypes */
        $pestRoutesServiceTypes = new Collection($pestRoutesServiceTypes->items);

        return $pestRoutesServiceTypes;
    }

    /**
     * @param AbstractHttpParams|SearchServiceTypesParams $params
     *
     * @return SearchServiceTypesParams
     */
    private function handleParams(AbstractHttpParams|SearchServiceTypesParams $params): SearchServiceTypesParams
    {
        $paramsArray = array_merge(self::PARAMS_DEFAULTS, $params->toArray());

        return new SearchServiceTypesParams(
            ids: $paramsArray['typeIDs'],
            officeIds: array_unique(array_merge($paramsArray['officeIDs'], [self::MUTUAL_OFFICE_ID])),
            description: $paramsArray['description'],
            category: $paramsArray['category'],
            isReservice: $paramsArray['reservice'] === null ? null : (bool) $paramsArray['reservice']
        );
    }
}
