<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Factories;

use App\Application\DTO\OverrideDTO;
use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use stdClass;

class EventOverrideFactory
{
    /**
     * @param stdClass $databaseObject
     *
     * @return Override
     */
    public function createFromRawData(stdClass $databaseObject): Override
    {
        $location = !empty($databaseObject->location)
            ? json_decode($databaseObject->location)
            : null;
        $address = empty($databaseObject->address)
            ? null
            : json_decode($databaseObject->address);

        return new Override(
            id: $databaseObject->id,
            eventId: $databaseObject->schedule_id,
            isCanceled: $databaseObject->is_canceled,
            date: Carbon::parse($databaseObject->date, $databaseObject->time_zone),
            eventDetails: new EventDetails(
                title: $databaseObject->title,
                description: $databaseObject->description,
                startTime: $databaseObject->start_time,
                endTime: $databaseObject->end_time,
                timeZone: CarbonTimeZone::create($databaseObject->time_zone),
                location: $location ? new Coordinate($location->lat, $location->lon) : null,
                meetingLink: $databaseObject->meeting_link,
                address: empty($address)
                    ? null
                    : new Address(
                        address: $address->address,
                        city: $address->city,
                        state: $address->state,
                        zip: $address->zip
                    )
            )
        );
    }

    /**
     * @param OverrideDTO $override
     *
     * @return Override
     */
    public function createFromDTO(OverrideDTO $override): Override
    {
        return new Override(
            id: $override->id,
            eventId: $override->eventId,
            isCanceled: $override->isCanceled,
            date: $override->date,
            eventDetails: new EventDetails(
                title: $override->title,
                description: $override->description,
                startTime: $override->startTime,
                endTime: $override->endTime,
                timeZone: $override->timeZone,
                location: $override->location,
                meetingLink: $override->meetingLink,
                address: $override->address
            )
        );
    }
}
