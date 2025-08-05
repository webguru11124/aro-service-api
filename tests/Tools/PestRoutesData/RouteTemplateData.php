<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\RouteTemplates\RouteTemplate;
use Tests\Tools\TestValue;

class RouteTemplateData extends AbstractTestPestRoutesData
{
    protected static function getSignature(): array
    {
        return [
            'templateID' => random_int(1, 130),
            'officeID' => TestValue::OFFICE_ID,
            'templateName' => 'Regular Routes',
            'officeDefault' => '0',
            'visible' => '1',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return RouteTemplate::class;
    }
}
