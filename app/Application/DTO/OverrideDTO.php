<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;

readonly class OverrideDTO
{
    public function __construct(
        public int|null $id,
        public int $eventId,
        public bool $isCanceled,
        public CarbonInterface $date,
        public string $title,
        public string $description,
        public string $startTime,
        public string $endTime,
        public CarbonTimeZone $timeZone,
        public Coordinate|null $location,
        public string|null $meetingLink = null,
        public Address|null $address = null
    ) {
    }
}
