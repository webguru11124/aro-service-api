<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Actions;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Services\PestRoutes\Scopes\PestRoutesBlockedSpotReasons;
use App\Domain\Contracts\Queries\GetEventsOnDateQuery;
use App\Domain\Contracts\Services\Actions\ReserveTimeForCalendarEvents;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Filters\DateFilter;

class PestRoutesReserveTimeForCalendarEvents implements ReserveTimeForCalendarEvents
{
    private Office $office;
    private CarbonInterface $date;

    /** @var Collection<RecurringEvent> */
    private Collection $events;

    /** @var Collection<Spot> */
    private Collection $spots;

    public function __construct(
        private SpotsDataProcessor $spotsDataProcessor,
        private GetEventsOnDateQuery $eventsQuery
    ) {
    }

    /**
     * Reserves time for calendar events by processing spots.
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return void
     */
    public function execute(Office $office, CarbonInterface $date): void
    {
        $this->office = $office;
        $this->date = $date;
        $this->resolveEvents();
        $this->resolveSpots();
        $this->processSpots();
    }

    private function resolveEvents(): void
    {
        $this->events = $this->eventsQuery->get($this->office->getId(), $this->date);
    }

    private function resolveSpots(): void
    {
        $params = new SearchSpotsParams(
            officeIds: [$this->office->getId()],
            date: DateFilter::between(
                $this->date->clone()->startOfDay()->toDateTime(),
                $this->date->clone()->endOfDay()->toDateTime()
            )
        );

        $this->spots = $this->spotsDataProcessor->extract($this->office->getId(), $params);
    }

    private function processSpots(): void
    {
        $spotsToUnblock = $this->resolveBlockedSpots();

        if ($spotsToUnblock->isNotEmpty()) {
            $this->spotsDataProcessor->unblockMultiple($this->office->getId(), $spotsToUnblock);
        }

        $spotsToBlock = $this->prepareSpotsToBlock();

        if ($spotsToBlock->isNotEmpty()) {
            foreach ($spotsToBlock as $eventId => $spots) {
                /** @var RecurringEvent $event */
                $event = $this->events->first(fn (RecurringEvent $event) => $event->getIdentity()->getId() === $eventId);
                $location = $event->getLocation() ?? $this->office->getLocation();
                $meeting = new Meeting(
                    $event->getIdentity()->getId(),
                    $event->getTitle(),
                    $event->getTimeWindow(),
                    $location
                );

                $this->spotsDataProcessor->blockMultiple($this->office->getId(), $spots, $meeting->getFormattedDescription());
            }
        }
    }

    private function resolveBlockedSpots(): Collection
    {
        return $this->spots->filter(
            fn (Spot $spot)
                => $spot->capacity === 0
                && is_string($spot->blockReason)
                && stripos($spot->blockReason, PestRoutesBlockedSpotReasons::CALENDAR_EVENT_MARKER) !== false
        );
    }

    private function prepareSpotsToBlock(): Collection
    {
        $spotsToBlock = new Collection();

        foreach ($this->events as $event) {
            $spotsToBlockForEvent = $this->getSpotsToBlock($event);
            if ($spotsToBlockForEvent->isNotEmpty()) {
                $spotsToBlock->put($event->getIdentity()->getId(), $spotsToBlockForEvent);
            }
        }

        return $spotsToBlock;
    }

    private function getSpotsToBlock(RecurringEvent $event): Collection
    {
        return $this->spots->filter(
            fn (Spot $spot) => $this->isSpotWithinTimeWindow($spot, $event->getTimeWindow()) && $event->isEmployeeInvited($spot->assignedEmployeeId)
        );
    }

    private function isSpotWithinTimeWindow(Spot $spot, TimeWindow $timeWindow): bool
    {
        $spotStart = Carbon::instance($spot->start);
        $spotEnd = Carbon::instance($spot->end);
        $start = $timeWindow->getStartAt();
        $end = $timeWindow->getEndAt();

        return ($spotStart->greaterThanOrEqualTo($start) && $spotStart->lessThan($end))
               || ($spotEnd->greaterThan($start) && $spotEnd->lessThanOrEqualTo($end));
    }
}
