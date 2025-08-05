<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PestRoutesSchedulingAppointmentTranslator
{
    private const INITIAL_APPOINTMENT = 'initial';

    public function __construct(
        private readonly PestRoutesCustomerTranslator $customerTranslator,
    ) {
    }

    public function toDomain(
        PestRoutesAppointment $appointment,
        PestRoutesServiceType|null $serviceType,
        PestRoutesCustomer $customer,
    ): Appointment {
        return new Appointment(
            id: $appointment->id,
            initial: $this->isInitial($serviceType),
            date: Carbon::instance($appointment->start),
            dateCompleted: $appointment->status === AppointmentStatus::Completed
                ? Carbon::instance($appointment->dateCompleted ?? $appointment->start)  // We need this due to PestRoutes issue when complete appointment has dateCompleted as null
                : null,
            customer: $this->customerTranslator->toDomain($customer),
            duration: Duration::fromMinutes($appointment->duration),
        );
    }

    private function isInitial(PestRoutesServiceType|null $serviceType): bool
    {
        return $serviceType !== null
            && str_contains(Str::lower($serviceType->description), Str::lower(self::INITIAL_APPOINTMENT));
    }
}
