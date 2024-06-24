<?php

declare(strict_types=1);

namespace App\Domain\Calendar\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonTimeZone;

class EventDetails
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $startTime,
        private readonly string $endTime,
        private readonly CarbonTimeZone $timeZone,
        private readonly Coordinate|null $location,
        private readonly string|null $meetingLink = null,
        private readonly Address|null $address = null,
    ) {
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getStartTime(): string
    {
        return $this->startTime;
    }

    /**
     * @return string
     */
    public function getEndTime(): string
    {
        return $this->endTime;
    }

    /**
     * @return CarbonTimeZone
     */
    public function getTimeZone(): CarbonTimeZone
    {
        return $this->timeZone;
    }

    /**
     * @return Coordinate|null
     */
    public function getLocation(): Coordinate|null
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getMeetingLink(): string|null
    {
        return $this->meetingLink;
    }

    /**
     * @return Address|null
     */
    public function getAddress(): Address|null
    {
        return $this->address;
    }
}
