<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;

readonly class EventDTO
{
    /**
     * @param int $officeId
     * @param string $title
     * @param string $description
     * @param string $startTime
     * @param string $endTime
     * @param CarbonTimeZone $timeZone
     * @param Coordinate|null $location
     * @param string $startDate
     * @param string $eventType
     * @param ScheduleInterval $interval
     * @param Collection<WeekDay> $weekDays
     * @param int $repeatEvery
     * @param EndAfter $endAfter
     * @param string|null $endDate
     * @param int|null $occurrences
     * @param string|null $meetingLink
     * @param Address|null $address
     */
    public function __construct(
        public int $officeId,
        public string $title,
        public string $description,
        public string $startTime,
        public string $endTime,
        public CarbonTimeZone $timeZone,
        public Coordinate|null $location,
        public string $startDate,
        public string $eventType,
        public ScheduleInterval $interval,
        public Collection $weekDays,
        public int $repeatEvery = 1,
        public EndAfter $endAfter = EndAfter::NEVER,
        public string|null $endDate = null,
        public int|null $occurrences = null,
        public string|null $meetingLink = null,
        public Address|null $address = null,
        public int|null $weekNumber = null,
    ) {
    }
}
