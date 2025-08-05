<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\HasRouteId;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;

class WorkBreak extends AbstractWorkEvent
{
    use HasRouteId;

    private const DESCRIPTION_TEMPLATE = '%d Min Break.';
    private const ESTIMATED_START_TEMPLATE = 'Est Start: %s';
    protected const ESTIMATED_START_TIME_FORMAT = 'H:iA T';

    protected int|null $minAppointmentsBefore = null;

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::BREAK;
    }

    public function getDuration(): Duration
    {
        if (isset($this->duration)) {
            return parent::getDuration();
        }

        $this->setDuration(new Duration(CarbonInterval::minutes(DomainContext::getWorkBreakDuration())));

        return parent::getDuration();
    }

    public function getFormattedFullDescription(): string
    {
        $fullDescription = sprintf(self::DESCRIPTION_TEMPLATE, $this->getDuration()->getTotalMinutes());

        if (!is_null($this->getTimeWindow())) {
            $fullDescription .= sprintf(
                ' ' . self::ESTIMATED_START_TEMPLATE,
                $this->getTimeWindow()->getStartAt()->format(self::ESTIMATED_START_TIME_FORMAT)
            );
        }

        return $fullDescription;
    }

    /**
     * @param int $minAppointmentsBefore
     *
     * @return $this
     */
    public function setMinAppointmentsBefore(int $minAppointmentsBefore): self
    {
        $this->minAppointmentsBefore = $minAppointmentsBefore;

        return $this;
    }

    /**
     * @return int
     */
    public function getMinAppointmentsBefore(): int|null
    {
        return $this->minAppointmentsBefore;
    }
}
