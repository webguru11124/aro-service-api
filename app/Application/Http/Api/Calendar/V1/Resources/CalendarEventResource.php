<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Resources;

use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var RecurringEvent $resource */
        $resource = $this->resource;

        return array_merge(
            [
                'id' => $resource->getIdentity()->getId(),
                'office_id' => $resource->getIdentity()->getOfficeId(),
                'start_date' => $resource->getStartDate()->toDateString(),
                'end_date' => $resource->getEndDate()?->toDateString(),
                'repeat_every' => $resource->getRepeatEvery(),
                'interval' => $resource->getInterval()->value,
                'week_days' => $resource->getWeekDaysAsCsv(),
                'week_num' => $resource->getWeekNum(),
                'title' => $resource->getTitle(),
                'description' => $resource->getDescription(),
                'event_type' => $resource->getEventType()->value,
                'start_at' => $resource->getStartTime(),
                'end_at' => $resource->getEndTime(),
                'time_zone' => $resource->getTimezoneFullName(),
                'override_id' => $resource->getOverrideId(),
                'is_canceled' => var_export($resource->isCanceled(), true),
                'service_pro_ids' => $resource->getParticipantIds()->values(),
                'location' => $resource->getLocation() ? $this->buildLocation($resource->getLocation()) : null,
                'meeting_link' => $resource->getMeetingLink(),
                'address' => $resource->getAddress() ? $this->getAddress($resource->getAddress()) : null,
            ],
        );
    }

    /**
     * @param Coordinate $location
     *
     * @return array<string, float>
     */
    private function buildLocation(Coordinate $location): array
    {
        return [
            'lat' => $location->getLatitude(),
            'lng' => $location->getLongitude(),
        ];
    }

    /**
     * @param Address $address
     *
     * @return array<string, string>
     */
    private function getAddress(Address $address): array
    {
        return [
            'address' => $address->getAddress(),
            'city' => $address->getCity(),
            'state' => $address->getState(),
            'zip' => $address->getZip(),
        ];
    }
}
