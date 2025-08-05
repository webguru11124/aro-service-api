<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events\Participants;

use App\Application\Http\Api\Calendar\V1\Requests\DeleteParticipantRequest;
use App\Application\Http\Api\Calendar\V1\Responses\ParticipantsDeletedResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Services\Calendar\CalendarService;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Routing\Controller;

class DeleteParticipantsEventsController extends Controller
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
        private CalendarService $officeCalendarService,
    ) {
    }

    /**
     * DELETE /api/v1/calendar/events/{event_id}/participants/{participant_id}
     *
     * @param DeleteParticipantRequest $request
     *
     * @return NotFoundResponse|ParticipantsDeletedResponse
     */
    public function __invoke(DeleteParticipantRequest $request): NotFoundResponse|ParticipantsDeletedResponse
    {
        $eventId = (int) $request->event_id;
        $participantId = (int) $request->participant_id;

        try {
            $event = $this->eventRepository->find($eventId);
        } catch (EventNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        $existingParticipantIds = $event->getParticipantIds()->toArray();

        if (!in_array($participantId, $existingParticipantIds)) {
            return new NotFoundResponse(__('messages.calendar.participant_not_found', [
                'participant_id' => $participantId,
            ]));
        }

        $this->officeCalendarService->deleteEventParticipants($event, $participantId);

        return new ParticipantsDeletedResponse();
    }
}
