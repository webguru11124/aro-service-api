<?php

declare(strict_types=1);

namespace App\Domain\Calendar\ValueObjects;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Exceptions\InvalidEventEnd;
use Carbon\CarbonInterface;

class EventEnd
{
    private CarbonInterface|null $date = null;
    private int|null $occurrences = null;

    public function __construct(
        private readonly EndAfter $endAfter,
        CarbonInterface|null $date = null,
        int|null $occurrences = null
    ) {
        if ($this->endAfter === EndAfter::DATE && $date === null) {
            throw InvalidEventEnd::instance();
        }

        if ($this->endAfter === EndAfter::OCCURRENCES && empty($occurrences)) {
            throw InvalidEventEnd::instance();
        }

        $this->date = $this->endAfter === EndAfter::DATE
            ? $date
            : null;

        $this->occurrences = $this->endAfter === EndAfter::OCCURRENCES
            ? $occurrences
            : null;
    }

    /**
     * @return EndAfter
     */
    public function getEndAfter(): EndAfter
    {
        return $this->endAfter;
    }

    /**
     * @return bool
     */
    public function isDate(): bool
    {
        return $this->endAfter === EndAfter::DATE;
    }

    /**
     * @return bool
     */
    public function isOccurrences(): bool
    {
        return $this->endAfter === EndAfter::OCCURRENCES;
    }

    /**
     * @return bool
     */
    public function isNever(): bool
    {
        return $this->endAfter === EndAfter::NEVER;
    }

    /**
     * @return CarbonInterface|null
     */
    public function getDate(): CarbonInterface|null
    {
        return $this->date;
    }

    /**
     * @return int|null
     */
    public function getOccurrences(): int|null
    {
        return $this->occurrences;
    }
}
