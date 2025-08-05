<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Application\DTO\EventDTO;
use App\Application\Http\Api\Calendar\V1\Requests\CreateEventRequest;
use App\Application\Http\Api\Calendar\V1\Responses\EventCreatedResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Services\Calendar\CalendarService;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;

class CreateEventController extends Controller
{
    public function __construct(
        private OfficeQuery $officeQuery,
        private CalendarService $officeCalendarService,
    ) {
    }

    /**
     * POST /api/v1/calendar/events
     *
     * @param CreateEventRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(CreateEventRequest $request): JsonResponse
    {
        try {
            $office = $this->officeQuery->get($request->integer('office_id'));
        } catch (OfficeNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        $id = $this->officeCalendarService->createEvent(new EventDTO(
            officeId: $request->integer('office_id'),
            title: $request->title,
            description: (string) $request->description,
            startTime: $request->start_at,
            endTime: $request->end_at,
            timeZone: $office->getTimeZone()->toRegionTimeZone(),
            location: $this->buildLocation($request),
            startDate: $request->start_date,
            eventType: $request->event_type,
            interval: ScheduleInterval::from($request->interval),
            weekDays: $this->buildWeekDays($request),
            repeatEvery: $request->integer('repeat_every', 1),
            endAfter: EndAfter::tryFrom($request->end_after),
            endDate: $request->end_date,
            occurrences: !empty($request->occurrences) ? $request->integer('occurrences') : null,
            meetingLink: $request->meeting_link,
            address: $this->buildAddress($request),
            weekNumber: $request->integer('week_num'),
        ));

        return new EventCreatedResponse($id);
    }

    /**
     * @param CreateEventRequest $request
     *
     * @return Collection<WeekDay>
     */
    private function buildWeekDays(CreateEventRequest $request): Collection
    {
        return collect(array_map(
            fn (string $weekday) => WeekDay::tryFrom($weekday),
            array_unique($request->week_days ?? [])
        ));
    }

    private function buildAddress(CreateEventRequest $request): Address|null
    {
        if (empty($request->address) && empty($request->city) && empty($request->state) && empty($request->zip)) {
            return null;
        }

        return new Address(
            address: $request->address,
            city: $request->city,
            state: $request->state,
            zip: $request->zip
        );
    }

    private function buildLocation(CreateEventRequest $request): Coordinate|null
    {
        return !empty($request->location_lat) && !empty($request->location_lng)
            ? new Coordinate((float) $request->location_lat, (float) $request->location_lng)
            : null;
    }
}
