<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events\Participants;

use App\Application\Http\Api\Calendar\V1\Requests\GetParticipantsRequest;
use App\Application\Http\Api\Calendar\V1\Responses\GetParticipantsResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Services\Calendar\CalendarEventParticipantsService;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class GetParticipantsEventsController extends Controller
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
        private CalendarEventParticipantsService $calendarEventParticipantsService,
    ) {
    }

    /**
     * GET /api/v1/calendar/events/{event_id}/participants
     *
     * @param GetParticipantsRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(GetParticipantsRequest $request): JsonResponse
    {
        $eventId = (int) $request->event_id;

        try {
            $event = $this->eventRepository->find($eventId);
        } catch (EventNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        $participants = $this->calendarEventParticipantsService->getParticipants($event);

        return new GetParticipantsResponse($participants);
    }
}
