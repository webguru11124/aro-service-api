<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

abstract class AbstractWorkEvent implements WorkEvent
{
    protected Duration $duration;
    protected TimeWindow|null $timeWindow;
    protected TimeWindow|null $expectedArrival = null;

    public function __construct(
        protected readonly int $id,
        protected readonly string $description,
    ) {
        $this->timeWindow = null;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if (isset($this->duration)) {
            $this->duration = clone $this->duration;
        }

        if (isset($this->timeWindow)) {
            $this->timeWindow = clone $this->timeWindow;
        }
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getFormattedDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return $this->duration;
    }

    /**
     * @param Duration $serviceDuration
     *
     * @return static
     */
    public function setDuration(Duration $serviceDuration): static
    {
        $this->duration = $serviceDuration;

        return $this;
    }

    /**
     * @param TimeWindow|null $timeWindow
     *
     * @return $this
     */
    public function setTimeWindow(TimeWindow|null $timeWindow): static
    {
        $this->timeWindow = $timeWindow;

        return $this;
    }

    /**
     * @return TimeWindow|null
     */
    public function getTimeWindow(): TimeWindow|null
    {
        return $this->timeWindow;
    }

    /**
     * @return TimeWindow|null
     */
    public function getExpectedArrival(): TimeWindow|null
    {
        return $this->expectedArrival;
    }

    /**
     * @param TimeWindow $expectedArrival
     *
     * @return static
     */
    public function setExpectedArrival(TimeWindow $expectedArrival): static
    {
        $this->expectedArrival = $expectedArrival;

        return $this;
    }
}
