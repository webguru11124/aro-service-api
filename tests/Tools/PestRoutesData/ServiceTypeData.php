<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType;
use Illuminate\Support\Collection;

class ServiceTypeData extends AbstractTestPestRoutesData
{
    public const MOSQUITO = 915;
    public const PREMIUM = 1801;
    public const PRO = 1799;
    public const PRO_PLUS = 1800;
    public const QUARTERLY_SERVICE = 21;
    public const RESERVICE = 3;
    public const INITIAL = 2;

    public const SERVICE_NAMES = [
        self::MOSQUITO => 'Mosquito Service - 30 day',
        self::PREMIUM => 'Premium',
        self::PRO => 'Pro',
        self::PRO_PLUS => 'Pro Plus',
        self::QUARTERLY_SERVICE => 'Quarterly Service',
        self::RESERVICE => 'Reservice',
        self::INITIAL => 'Initial Service',
    ];

    protected static function getSignature(): array
    {
        return [
            'typeID' => (string) self::PRO,
            'officeID' => '-1',
            'description' => self::SERVICE_NAMES[self::QUARTERLY_SERVICE],
            'frequency' => '90',
            'defaultCharge' => '0.00',
            'category' => 'GENERAL',
            'reservice' => '0',
            'defaultLength' => '30',
            'defaultInitialCharge' => null,
            'initialID' => '2',
            'minimumRecurringCharge' => '0.00',
            'minimumInitialCharge' => '0.00',
            'regularService' => '1',
            'initial' => '0',
            'glAccountID' => '0',
            'sentricon' => '0',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return ServiceType::class;
    }

    public static function getTestDataOfTypes(int ...$serviceTypeIds): Collection
    {
        return self::getTestData(
            count($serviceTypeIds),
            ...array_map(fn (int $serviceTypeId) => [
                'typeID' => $serviceTypeId,
                'description' => self::SERVICE_NAMES[$serviceTypeId],
                'reservice' => $serviceTypeId === self::RESERVICE ? '1' : '0',
                'initial' => $serviceTypeId === self::INITIAL ? '1' : '0',
            ], $serviceTypeIds)
        );
    }
}
