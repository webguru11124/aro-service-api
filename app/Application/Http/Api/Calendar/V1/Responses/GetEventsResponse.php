<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarEventResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\Calendar\Entities\RecurringEvent;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;

class GetEventsResponse extends AbstractResponse
{
    private const DEFAULT_PER_PAGE = 10;
    private const DEFAULT_PAGE = 1;

    /** @var Collection */
    private Collection $particularEventsGroupedByDate;

    /**
     * @param Collection<RecurringEvent> $particularEvents
     * @param int|null $page
     * @param int|null $perPage
     */
    public function __construct(Collection $particularEvents, int|null $page, int|null $perPage)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);

        $page = $page ?? self::DEFAULT_PAGE;
        $perPage = $perPage ?? self::DEFAULT_PER_PAGE;

        $this->particularEventsGroupedByDate = $particularEvents->groupBy(
            fn (RecurringEvent $event) => $event->getDate()->toDateString()
        );

        $this->setResult($this->buildFormattedSchedules($page, $perPage));

        $total = $this->particularEventsGroupedByDate->count();
        $totalPages = (int) ceil($total / $perPage);

        $this->addMetadata('pagination', [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    /**
     * @param int $page
     * @param int $perPage
     *
     * @return array<string, array<array<int, array<string, mixed>>>>
     */
    private function buildFormattedSchedules(int $page, int $perPage): array
    {
        $formatted = [];
        $offset = ($page - 1) * $perPage;
        $list = $this->particularEventsGroupedByDate->slice($offset, $perPage);

        foreach ($list as $date => $particularEvents) {
            /** @var RecurringEvent $event */
            foreach ($particularEvents as $event) {
                $formatted[$date][] = $this->formatEvent($event);
            }
        }

        return ['events' => $formatted];
    }

    /**
     * @param RecurringEvent $event
     *
     * @return array<string, mixed>
     */
    private function formatEvent(RecurringEvent $event): array
    {
        return CalendarEventResource::make($event)->toArray(request());
    }
}
