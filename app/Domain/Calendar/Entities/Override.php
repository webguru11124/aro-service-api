<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Entities;

use App\Domain\Calendar\ValueObjects\EventDetails;
use Carbon\CarbonInterface;

class Override
{
    public function __construct(
        private int $id,
        private int $eventId,
        private bool $isCanceled,
        private CarbonInterface $date,
        private EventDetails $eventDetails,
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->eventId;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return EventDetails
     */
    public function getEventDetails(): EventDetails
    {
        return $this->eventDetails;
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->isCanceled;
    }
}
