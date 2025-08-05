<?php

declare(strict_types=1);

namespace App\Infrastructure\Dto;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\PestRoutes\Enums\AppointmentType;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\Window;

class CreateAppointmentDto
{
    public function __construct(
        public readonly Office $office,
        public readonly int $customerId,
        public readonly int $spotId,
        public readonly int $subscriptionId,
        public readonly AppointmentType $appointmentType,
        public readonly bool $isAroSpot,
        public readonly Window $window,
        public readonly RequestingSource $requestingSource,
        public readonly string|null $notes = null
    ) {
    }
}
