<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Contracts\Queries\Office\OfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesOfficeTranslator;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Filters\NumberFilter;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;

class PestRoutesOfficesByIdsQuery implements OfficesByIdsQuery
{
    public const GLOBAL_OFFICE_ID = 0;

    public function __construct(
        private readonly OfficesDataProcessor $officesDataProcessor,
        private readonly PestRoutesOfficeTranslator $translator
    ) {
    }

    /**
     * @param int ...$officeId
     *
     * @return Collection<Office>
     * @throws OfficeNotFoundException
     * @throws InternalServerErrorHttpException
     */
    public function get(int ...$officeId): Collection
    {
        $pestRoutesOffices = $this->getPestRoutesOffices($officeId);

        $existingOfficeIds = $pestRoutesOffices->pluck('id')->all();
        $nonExistingOfficeIds = array_diff($officeId, $existingOfficeIds);

        if (!empty($nonExistingOfficeIds)) {
            throw OfficeNotFoundException::instance($nonExistingOfficeIds);
        }

        return $this->mapOfficesToDomain($pestRoutesOffices);
    }

    private function mapOfficesToDomain(Collection $pestRoutesOffices): Collection
    {
        return $pestRoutesOffices->map(fn (PestRoutesOffice $office) => $this->translator->toDomain($office));
    }

    /**
     * @param int[] $ids
     *
     * @return Collection<PestRoutesOffice>
     * @throws InternalServerErrorHttpException
     */
    private function getPestRoutesOffices(array $ids): Collection
    {
        $searchParams = new SearchOfficesParams(
            officeId: NumberFilter::in($ids)
        );

        return $this->officesDataProcessor->extract(self::GLOBAL_OFFICE_ID, $searchParams);
    }
}
