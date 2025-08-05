<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Entities\ServiceHistory;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Carbon\Carbon;

class PestRoutesServiceHistoryTranslator
{
    public function __construct(
        private readonly PestRoutesServiceTypeTranslator $serviceTypeTranslator
    ) {
    }

    public function toDomain(
        PestRoutesAppointment $pestRoutesAppointment,
        PestRoutesServiceType $pestRoutesServiceType
    ): ServiceHistory {
        $checkIn = Carbon::instance($pestRoutesAppointment->checkIn);
        $checkOut = Carbon::instance($pestRoutesAppointment->checkOut);
        $duration = Duration::fromSeconds($checkIn->diffInSeconds($checkOut));

        return new ServiceHistory(
            id: $pestRoutesAppointment->id,
            customerId: $pestRoutesAppointment->customerId,
            serviceType: $this->serviceTypeTranslator->toDomain($pestRoutesServiceType),
            duration: $duration,
            date: $checkIn,
        );
    }
}
