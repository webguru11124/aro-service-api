<?php

declare(strict_types=1);

namespace App\Application\Transformer;

use App\Application\DTO\OverrideDTO;
use App\Application\Http\Api\Calendar\V1\Requests\EventOverrideRequest;
use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;

class OverrideDtoTransformer
{
    private RecurringEvent|null $recurringEvent;
    private Event $existingEvent;

    public function __construct(
        private CalendarEventRepository $eventRepository
    ) {
    }

    /**
     * @param EventOverrideRequest $request
     * @param Event $existingEvent
     *
     * @return OverrideDTO
     */
    public function transformFromRequest(
        EventOverrideRequest $request,
        Event $existingEvent
    ): OverrideDTO {
        $timeZone = $existingEvent->getEventDetails()->getTimeZone();
        $date = Carbon::parse($request->get('date'), $timeZone);
        $this->existingEvent = $existingEvent;
        $this->recurringEvent = $existingEvent->getRecurringEventOnDate($date);

        return new OverrideDTO(
            id: $this->eventRepository->getEventOverridesNextId(),
            eventId: $existingEvent->getId(),
            isCanceled: $request->get('is_canceled', $existingEvent->isCanceledOnDate($date)),
            date: $date,
            title: $request->get('title') ?? $this->getTitle(),
            description: $this->getDescription($request),
            startTime: $request->get('start_time') ?? $this->getStartTime(),
            endTime: $request->get('end_time') ?? $this->getEndTime(),
            timeZone: $timeZone,
            location: $this->getLocation($request),
            meetingLink: $request->get('meeting_link') ?? $this->getMeetingLink(),
            address: $this->getAddress($request)
        );
    }

    /**
     * @param EventOverrideRequest $request
     *
     * @return Coordinate|null
     */
    private function getLocation(EventOverrideRequest $request): Coordinate|null
    {
        $defaultLocation = $this->recurringEvent
            ? $this->recurringEvent->getLocation()
            : $this->existingEvent->getEventDetails()->getLocation();

        if (!$defaultLocation) {
            return null;
        }

        return new Coordinate(
            (float) ($request->get('location_lat') ?? $defaultLocation->getLatitude()),
            (float) ($request->get('location_lng') ?? $defaultLocation->getLongitude()),
        );
    }

    private function getTitle(): string
    {
        return $this->recurringEvent
            ? $this->recurringEvent->getTitle()
            : $this->existingEvent->getEventDetails()->getTitle();
    }

    private function getDescription(EventOverrideRequest $request): string
    {
        if (!$request->has('description')) {
            return $this->recurringEvent
                ? $this->recurringEvent->getDescription()
                : $this->existingEvent->getEventDetails()->getDescription();
        }

        if (empty($request->get('description'))) {
            return '';
        }

        return $request->get('description');
    }

    private function getStartTime(): string
    {
        return $this->recurringEvent
            ? $this->recurringEvent->getStartTime()
            : $this->existingEvent->getEventDetails()->getStartTime();
    }

    private function getEndTime(): string
    {
        return $this->recurringEvent
            ? $this->recurringEvent->getEndTime()
            : $this->existingEvent->getEventDetails()->getEndTime();
    }

    private function getAddress(EventOverrideRequest $request): Address|null
    {
        $defaultAddress = $this->recurringEvent
            ? $this->recurringEvent->getAddress()
            : $this->existingEvent->getEventDetails()->getAddress();

        if (
            empty($request->get('address'))
            && empty($request->get('city'))
            && empty($request->get('state'))
            && empty($request->get('zip'))
        ) {
            return null;
        }

        return new Address(
            address: $request->get('address') ?? $defaultAddress?->getAddress(),
            city: $request->get('city') ?? $defaultAddress?->getCity(),
            state: $request->get('state') ?? $defaultAddress?->getState(),
            zip: $request->get('zip') ?? $defaultAddress?->getZip(),
        );
    }

    private function getMeetingLink(): string|null
    {
        return $this->recurringEvent
            ? $this->recurringEvent->getMeetingLink()
            : $this->existingEvent->getEventDetails()->getMeetingLink();
    }
}
