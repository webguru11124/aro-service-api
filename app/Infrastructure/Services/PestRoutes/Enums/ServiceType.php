<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Enums;

enum ServiceType: int
{
    case RESERVICE = 3;
    case INITIAL = 2;
    case QUARTERLY = 21;
    case BASIC = 1799;
    case PRO = 2827;
    case PRO_PLUS = 1800;
    case PREMIUM = 2828;

    /**
     * @return AppointmentType
     */
    public function toAppointmentType(): AppointmentType
    {
        $name = $this->name;

        return AppointmentType::{$name};
    }
}
