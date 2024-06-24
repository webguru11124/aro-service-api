<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;

readonly class TimeWindow
{
    public function __construct(
        private CarbonInterface $startAt,
        private CarbonInterface $endAt,
    ) {
        if ($this->endAt < $this->startAt) {
            throw InvalidTimeWindowException::instance($this->startAt, $this->endAt);
        }
    }

    /**
     * @return CarbonInterface
     */
    public function getStartAt(): CarbonInterface
    {
        return $this->startAt;
    }

    /**
     * @return CarbonInterface
     */
    public function getEndAt(): CarbonInterface
    {
        return $this->endAt;
    }

    /**
     * @return int
     */
    public function getTotalMinutes(): int
    {
        return $this->startAt->diffInMinutes($this->endAt);
    }

    /**
     * @return int
     */
    public function getTotalSeconds(): int
    {
        return $this->startAt->diffInSeconds($this->endAt);
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return Duration::fromSeconds($this->endAt->diffInSeconds($this->startAt));
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isDateInTimeWindow(CarbonInterface $date): bool
    {
        return $this->getStartAt() <= $date && $this->getEndAt() >= $date;
    }

    /**
     * @return bool
     */
    public function isWholeDay(): bool
    {
        $startOfDay = $this->getStartAt()->clone()->startOfDay();
        $endOfDay = $this->getStartAt()->clone()->endOfDay();

        return $this->getStartAt() == $startOfDay && $this->getEndAt() == $endOfDay;
    }

    /**
     * Searches for an intersection between two time windows
     *
     * @param TimeWindow $timeWindow
     * @param CarbonTimeZone|null $timeZone
     *
     * @return TimeWindow|null
     */
    public function getIntersection(TimeWindow $timeWindow, CarbonTimeZone|null $timeZone = null): TimeWindow|null
    {
        $maxStartTimeStamp = max(
            $this->getStartAt()->timestamp,
            $timeWindow->getStartAt()->timestamp
        );

        $minEndTimeStamp = min(
            $this->getEndAt()->timestamp,
            $timeWindow->getEndAt()->timestamp
        );

        if ($maxStartTimeStamp >= $minEndTimeStamp) {
            return null;
        }

        $timeZone = $timeZone ?: $this->getStartAt()->getTimezone();

        return new TimeWindow(
            Carbon::createFromTimestamp($maxStartTimeStamp, $timeZone),
            Carbon::createFromTimestamp($minEndTimeStamp, $timeZone),
        );
    }
}
