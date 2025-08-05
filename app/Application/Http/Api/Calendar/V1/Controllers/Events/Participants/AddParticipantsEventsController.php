<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events\Participants;

use App\Application\Http\Api\Calendar\V1\Requests\AddParticipantsRequest;
use App\Application\Http\Api\Calendar\V1\Responses\ParticipantsAddedResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Services\Calendar\CalendarService;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AddParticipantsEventsController extends Controller
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
        private CalendarService $officeCalendarService,
    ) {
    }

    /**
     * PUT /api/v1/calendar/events/{event_id}/participants
     *
     * @param AddParticipantsRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(AddParticipantsRequest $request): JsonResponse
    {
        $newParticipantIds = $request->participant_ids;
        $eventId = (int) $request->event_id;

        try {
            $event = $this->eventRepository->find($eventId);
        } catch (EventNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        $this->officeCalendarService->updateEventParticipants($event, $newParticipantIds);

        return new ParticipantsAddedResponse();
    }
}
