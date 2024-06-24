<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Carbon\CarbonTimeZone;

class PestRoutesOfficeTranslator
{
    public function toDomain(
        PestRoutesOffice $pestRoutesOffice,
    ): Office {
        return new Office(
            id: $pestRoutesOffice->id,
            name: $pestRoutesOffice->officeName,
            region: '',
            address: new Address(
                address: $pestRoutesOffice->address,
                city: $pestRoutesOffice->city,
                state: $pestRoutesOffice->state,
                zip: $pestRoutesOffice->zip,
            ),
            timezone: new CarbonTimeZone($pestRoutesOffice->timeZone),
            location: new Coordinate(0, 0),
        );
    }
}
