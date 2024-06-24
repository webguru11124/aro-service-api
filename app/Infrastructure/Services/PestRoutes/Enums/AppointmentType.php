<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Enums;

enum AppointmentType: int
{
    case RESERVICE = 0;
    case INITIAL = 1;
    case QUARTERLY = 2;
    case BASIC = 3;
    case PRO = 4;
    case PRO_PLUS = 5;
    case PREMIUM = 6;

    /**
     * @return ServiceType
     */
    public function toServiceType(): ServiceType
    {
        $name = $this->name;

        return ServiceType::{$name};
    }
}
