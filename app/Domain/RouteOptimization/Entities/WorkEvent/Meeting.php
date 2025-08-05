<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\HasRouteId;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;

class Meeting extends AbstractWorkEvent
{
    use HasRouteId;

    private const BLOCKED_SPOT_PREFIX = '#EVENT';

    public function __construct(
        int $id,
        string $description,
        TimeWindow $timeWindow,
        private Coordinate $location,
    ) {
        parent::__construct($id, $description);

        $this->timeWindow = $timeWindow;
        $this->setExpectedArrival(clone $timeWindow);
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return $this->timeWindow->getDuration();
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::MEETING;
    }

    /**
     * @return string
     */
    public function getFormattedDescription(): string
    {
        return sprintf(
            '%s %s [%s], [%g, %g]',
            self::BLOCKED_SPOT_PREFIX,
            $this->getDescription(),
            $this->getExpectedArrival()->getStartAt()->format('H:i') . ' - ' . $this->getExpectedArrival()->getEndAt()->format('H:i'),
            round($this->getLocation()->getLatitude(), 5),
            round($this->getLocation()->getLongitude(), 5),
        );
    }
}
