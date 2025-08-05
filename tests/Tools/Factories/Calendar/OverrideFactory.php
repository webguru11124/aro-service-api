<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Calendar;

use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Tests\Tools\Factories\AbstractFactory;
use Tests\Tools\TestValue;

class OverrideFactory extends AbstractFactory
{
    public function single($overrides = []): Override
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(2);
        $eventId = $overrides['eventId'] ?? $this->faker->randomNumber(2);
        $isCanceled = $overrides['isCanceled'] ?? false;
        $title = $overrides['title'] ?? $this->faker->title();
        $description = $overrides['description'] ?? $this->faker->text(36);
        $startTime = $overrides['startTime'] ?? '08:00:00';
        $endTime = $overrides['endTime'] ?? '08:30:00';
        $timeZone = $overrides['timeZone'] ?? CarbonTimeZone::create(TestValue::TIME_ZONE);
        $location = $overrides['location'] ?? new Coordinate(30.1234, -70.4532);
        $date = $overrides['date'] ?? Carbon::tomorrow($timeZone);
        $address = $overrides['address'] ?? new Address(
            address: $this->faker->streetAddress(),
            city: $this->faker->city(),
            state: 'LA',
            zip: '66666'
        );
        $meetingLink = $overrides['meetingLink'] ?? $this->faker->url;

        $eventDetails = new EventDetails(
            title: $title,
            description: $description,
            startTime: $startTime,
            endTime: $endTime,
            timeZone: $timeZone,
            location: $location,
            meetingLink: $meetingLink,
            address: $address,
        );

        return new Override(
            id: $id,
            eventId: $eventId,
            isCanceled: $isCanceled,
            date: $date,
            eventDetails: $eventDetails
        );
    }
}
