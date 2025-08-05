<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Application\Http\Api\Calendar\V1\Requests\EventOverrideRequest;
use App\Application\Http\Api\Calendar\V1\Responses\EventOverrideResponse;
use App\Application\Http\Responses\BadRequestResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Services\Calendar\CalendarService;
use App\Application\Transformer\OverrideDtoTransformer;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Calendar\Exceptions\OverrideOutOfEventRecurrenceException;
use App\Domain\Calendar\Factories\EventOverrideFactory;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class EventOverrideController extends Controller
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
        private CalendarService $officeCalendarService,
        private EventOverrideFactory $eventOverrideFactory,
        private OverrideDtoTransformer $overrideDtoTransformer,
    ) {
    }

    /**
     * PUT /api/v1/calendar/events/{event_id}/overrides
     *
     * @param EventOverrideRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(EventOverrideRequest $request, int $eventId): JsonResponse
    {
        try {
            $event = $this->eventRepository->find($eventId);
        } catch (EventNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        $override = $this->eventOverrideFactory->createFromDTO(
            $this->overrideDtoTransformer->transformFromRequest($request, $event),
        );

        try {
            $this->officeCalendarService->updateCalendarEventOverride($event, $override);
        } catch (OverrideOutOfEventRecurrenceException $exception) {
            return new BadRequestResponse($exception->getMessage());
        }

        return new EventOverrideResponse();
    }
}
