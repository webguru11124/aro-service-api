<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Enums;

enum RequestingSource: string
{
    case CUSTOMER_PORTAL = 'CXP';
    case FLEX_IVR = 'IVR';
    case SELF_CHECKOUT = 'SCO';
    case FLEX_SMS = 'SMS';
    case MARKETING_PARTNER = 'MKT';
    case TEST = 'TEST';
}
