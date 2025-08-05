<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

use Carbon\CarbonInterval;

readonly class Duration
{
    private const OUT_OF_BOUNDS_ERROR_MESSAGE = 'Only days, hours, minutes and seconds can be set in the Duration';

    public function __construct(
        private CarbonInterval $interval,
    ) {
        if ($this->isIntervalOutOfBounds()) {
            throw new \OutOfBoundsException(self::OUT_OF_BOUNDS_ERROR_MESSAGE);
        }
    }

    /**
     * @param int $seconds
     *
     * @return Duration
     */
    public static function fromSeconds(int $seconds): Duration
    {
        return new Duration(CarbonInterval::seconds($seconds));
    }

    /**
     * @param int $minutes
     *
     * @return Duration
     */
    public static function fromMinutes(int $minutes): Duration
    {
        return new Duration(CarbonInterval::minutes($minutes));
    }

    /**
     * @return CarbonInterval
     */
    public function getInterval(): CarbonInterval
    {
        return $this->interval;
    }

    /**
     * @return int
     */
    public function getTotalHours(): int
    {
        return (int) floor($this->getTotalMinutes() / 60);
    }

    /**
     * @return int
     */
    public function getTotalMinutes(): int
    {
        return (int) floor($this->getTotalSeconds() / 60);
    }

    /**
     * @return int
     */
    public function getTotalSeconds(): int
    {
        return ($this->interval->d * 24 * 60 * 60)
            + ($this->interval->h * 60 * 60)
            + $this->interval->i * 60
            + $this->interval->s;
    }

    /**
     * @param Duration $duration
     *
     * @return Duration
     */
    public function increase(Duration $duration): Duration
    {
        return new self($this->interval->clone()->add($duration->getInterval()));
    }

    /**
     * @param Duration $duration
     *
     * @return Duration
     */
    public function decrease(Duration $duration): Duration
    {
        if ($this->interval->lessThanOrEqualTo($duration->getInterval())) {
            return new self(CarbonInterval::seconds(0));
        }

        return new self($this->interval->clone()->sub($duration->getInterval()));
    }

    /**
     * Returns a string formatted like "1h 11m"
     *
     * @return string
     */
    public function format(): string
    {
        $hours = $this->getTotalHours();
        $minutes = $this->getTotalMinutes();

        $minutes = $minutes - ($hours * 60);
        $output = "{$hours}h {$minutes}m";

        return $output;
    }

    private function isIntervalOutOfBounds(): bool
    {
        return ($this->interval->y || $this->interval->m || $this->interval->f);
    }
}
