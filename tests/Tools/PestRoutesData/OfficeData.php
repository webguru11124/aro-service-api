<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\Offices\Office;

class OfficeData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Office::class;
    }

    protected static function getSignature(): array
    {
        $return = [
            'officeID' => random_int(1, 130),
            'officeName' => 'Demo Demo Atlanta East',
            'companyID' => '1',
            'licenseNumber' => '100312',
            'contactNumber' => '7706852847',
            'contactEmail' => 'customersupport@goaptive.com',
            'website' => 'https://www.goaptive.com',
            'timeZone' => 'America/New_York',
            'address' => '1960 parker ct suite B',
            'city' => 'stone mountain',
            'state' => 'GA',
            'zip' => '30087',
            'invoiceAddress' => 'PO Box 736025',
            'invoiceCity' => 'Dallas',
            'invoiceState' => 'TX',
            'invoiceZip' => '75373-6025',
        ];

        return $return;
    }
}
