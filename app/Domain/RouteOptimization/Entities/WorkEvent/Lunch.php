<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\HasRouteId;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;

class Lunch extends WorkBreak
{
    use HasRouteId;

    private const DESCRIPTION_TEMPLATE = 'Lunch Break.';
    private const ESTIMATED_START_TEMPLATE = 'Est Start: %s';

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::LUNCH;
    }

    public function getDuration(): Duration
    {
        if (isset($this->duration)) {
            return parent::getDuration();
        }

        $this->setDuration(new Duration(CarbonInterval::minutes(DomainContext::getLunchDuration())));

        return parent::getDuration();
    }

    public function getFormattedFullDescription(): string
    {
        $fullDescription = self::DESCRIPTION_TEMPLATE;

        if (!is_null($this->getTimeWindow())) {
            $fullDescription .= sprintf(
                ' ' . self::ESTIMATED_START_TEMPLATE,
                $this->getTimeWindow()->getStartAt()->format(self::ESTIMATED_START_TIME_FORMAT)
            );
        }

        return $fullDescription;
    }
}
