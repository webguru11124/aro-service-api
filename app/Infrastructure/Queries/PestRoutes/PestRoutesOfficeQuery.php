<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesOfficeTranslator;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;

class PestRoutesOfficeQuery implements OfficeQuery
{
    public function __construct(
        private readonly OfficesDataProcessor $officesDataProcessor,
        private readonly PestRoutesOfficeTranslator $translator
    ) {
    }

    /**
     * @param int $officeId
     *
     * @return Office
     * @throws OfficeNotFoundException
     */
    public function get(int $officeId): Office
    {
        /** @var PestRoutesOffice $pestRoutesOffice */
        $pestRoutesOffice = $this->officesDataProcessor->extract($officeId, new SearchOfficesParams(
            officeId: $officeId
        ))->first();

        if ($pestRoutesOffice === null) {
            throw OfficeNotFoundException::instance([$officeId]);
        }

        return $this->translator->toDomain($pestRoutesOffice);
    }
}
