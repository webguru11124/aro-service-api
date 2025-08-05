<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Event;

use App\Application\Http\Api\Calendar\V1\Requests\UpdateEventRequest;
use App\Application\Http\Api\Calendar\V1\Responses\UpdateEventResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Illuminate\Http\JsonResponse;

class UpdateEventController
{
    /**
     * PATCH /calendar/events/{event_id}
     *
     * Edit an event
     *
     * @param UpdateEventRequest $request
     * @param CalendarEventRepository $calendarEventRepository
     *
     * @return JsonResponse
     */
    public function __invoke(UpdateEventRequest $request, CalendarEventRepository $calendarEventRepository): JsonResponse
    {
        $eventId = (int) $request->input('event_id');

        try {
            $event = $calendarEventRepository->find($eventId);
        } catch (EventNotFoundException $e) {
            return new NotFoundResponse($e->getMessage());
        }

        $title = $request->input('title');
        $description = $request->input('description') ?? '';
        $locationLat = $request->input('location_lat');
        $locationLng = $request->input('location_lng');
        $meetingLink = $request->input('meeting_link');
        $address = $request->input('address') ?? '';
        $city = $request->input('city') ?? '';
        $state = $request->input('state') ?? '';
        $zip = $request->input('zip') ?? '';

        $coordinate = ($locationLat && $locationLng) ? new Coordinate($locationLat, $locationLng) : null;

        $event->updateDetails(
            title: $title,
            description: $description,
            location: $coordinate,
            meetingLink: $meetingLink,
            address: new Address($address, $city, $state, $zip),
        );

        $calendarEventRepository->update($event);

        return new UpdateEventResponse($eventId);
    }
}
