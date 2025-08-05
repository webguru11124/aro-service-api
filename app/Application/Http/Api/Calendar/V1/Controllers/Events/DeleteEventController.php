<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Application\Http\Api\Calendar\V1\Requests\DeleteEventRequest;
use App\Application\Http\Api\Calendar\V1\Responses\EventDeletedResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class DeleteEventController extends Controller
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
    ) {
    }

    /**
     * DELETE /api/v1/calendar/events/{event_id}
     * Soft deletes the calendar and related data corresponding to the specified ID.
     *
     * @param DeleteEventRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(DeleteEventRequest $request): JsonResponse
    {
        $eventId = (int) $request->event_id;

        try {
            $this->eventRepository->find($eventId);
        } catch (EventNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        $this->eventRepository->delete($eventId);

        return new EventDeletedResponse($eventId);
    }
}
