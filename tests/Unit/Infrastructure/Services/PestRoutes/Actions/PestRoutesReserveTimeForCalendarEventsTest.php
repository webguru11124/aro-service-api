<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Actions;

use App\Domain\Contracts\Queries\GetEventsOnDateQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Services\PestRoutes\Actions\PestRoutesReserveTimeForCalendarEvents;
use App\Infrastructure\Services\PestRoutes\Scopes\PestRoutesBlockedSpotReasons;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Calendar\RecurringEventFactory;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use App\Domain\SharedKernel\ValueObjects\Coordinate;

class PestRoutesReserveTimeForCalendarEventsTest extends TestCase
{
    private MockInterface|SpotsDataProcessor $mockSpotsDataProcessor;
    private MockInterface|GetEventsOnDateQuery $mockEventsQuery;
    private PestRoutesReserveTimeForCalendarEvents $action;
    private Office $office;
    private CarbonInterface $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSpotsDataProcessor = Mockery::mock(SpotsDataProcessor::class);
        $this->mockEventsQuery = Mockery::mock(GetEventsOnDateQuery::class);
        $this->action = new PestRoutesReserveTimeForCalendarEvents($this->mockSpotsDataProcessor, $this->mockEventsQuery);
        $this->office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);
        $this->date = Carbon::today(TestValue::CUSTOMER_TIME_ZONE);
    }

    /**
     * @test
     */
    public function it_adds_new_blocked_spots_for_event(): void
    {
        $location = new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE);
        $events = collect([
            $this->createEvent($this->date->clone()->hour(12), $this->date->clone()->hour(13), [TestValue::EMPLOYEE1_ID], $location),
            $this->createEvent($this->date->clone()->hour(14), $this->date->clone()->hour(15), [TestValue::EMPLOYEE1_ID], $location),
        ]);

        $spots = SpotData::getTestData(
            4,
            ['spotID' => 1, 'start' => '08:00:00', 'end' => '08:29:00', 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
            ['spotID' => 2, 'start' => '09:00:00', 'end' => '09:29:00', 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
            ['spotID' => 3, 'start' => '14:00:00', 'end' => '14:29:00', 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
            ['spotID' => 4, 'start' => '14:30:00', 'end' => '14:59:00', 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0]
        );

        $this->setUpMockEventsQuery($events);
        $this->setUpMockSpotsDataProcessor($spots);

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->with(
                $this->office->getId(),
                Mockery::on(function (Collection $spots) {
                    return $spots->count() === 2 && $spots->contains(fn ($spot) => $spot->id === 3) && $spots->contains(fn ($spot) => $spot->id === 4);
                }),
                Mockery::on(function ($description) use ($events) {
                    $event = $events->last();

                    return $this->assertSpotBlockReason($description, $event, $event->getLocation());
                })
            );

        $this->action->execute($this->office, $this->date);
    }

    /**
     * @test
     */
    public function it_defaults_to_office_location_when_no_event_location(): void
    {
        $events = collect([
            $this->createEvent($this->date->clone()->hour(12), $this->date->clone()->hour(13), [TestValue::EMPLOYEE1_ID]),
        ]);

        $spots = SpotData::getTestData(
            2,
            ['spotID' => 3, 'start' => '12:00:00', 'end' => '12:29:00', 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
            ['spotID' => 4, 'start' => '12:30:00', 'end' => '12:59:00', 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0]
        );

        $this->setUpMockEventsQuery($events);
        $this->setUpMockSpotsDataProcessor($spots);

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->with(
                $this->office->getId(),
                Mockery::on(function (Collection $spots) {
                    return $spots->count() === 2 && $spots->contains(fn ($spot) => $spot->id === 3) && $spots->contains(fn ($spot) => $spot->id === 4);
                }),
                Mockery::on(function ($description) use ($events) {
                    $event = $events->first();

                    return $this->assertSpotBlockReason($description, $event, $this->office->getLocation());
                })
            );

        $this->action->execute($this->office, $this->date);
    }

    /**
     * @test
     */
    public function it_does_not_block_spots_when_employee_not_invited(): void
    {
        $events = collect([
            $this->createEvent($this->date->clone()->hour(14), $this->date->clone()->hour(15), [TestValue::EMPLOYEE2_ID]),
        ]);

        $spots = SpotData::getTestData(
            2,
            ['spotID' => 3, 'start' => '14:00:00', 'end' => '14:29:00', 'assignedTech' => TestValue::EMPLOYEE1_ID],
            ['spotID' => 4, 'start' => '14:30:00', 'end' => '14:59:00', 'assignedTech' => TestValue::EMPLOYEE1_ID]
        );

        $this->setUpMockEventsQuery($events);
        $this->setUpMockSpotsDataProcessor($spots);

        $this->mockSpotsDataProcessor
            ->shouldNotReceive('blockMultiple');

        $this->action->execute($this->office, $this->date);
    }

    /**
     * @test
     */
    public function it_releases_blocked_spots_when_event_removed(): void
    {
        $events = collect();

        $spots = SpotData::getTestData(
            2,
            ['spotID' => 3, 'start' => '14:00:00', 'end' => '14:29:00', 'blockReason' => PestRoutesBlockedSpotReasons::CALENDAR_EVENT_MARKER, 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
            ['spotID' => 4, 'start' => '14:30:00', 'end' => '14:59:00', 'blockReason' => PestRoutesBlockedSpotReasons::CALENDAR_EVENT_MARKER, 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0]
        );

        $this->setUpMockEventsQuery($events);
        $this->setUpMockSpotsDataProcessor($spots, true);

        $this->mockSpotsDataProcessor
            ->shouldNotReceive('blockMultiple');

        $this->action->execute($this->office, $this->date);
    }

    /**
     * @test
     */
    public function it_releases_blocked_spots_when_employee_not_invited(): void
    {
        $events = collect([
            $this->createEvent($this->date->clone()->hour(8), $this->date->clone()->hour(10), [TestValue::EMPLOYEE2_ID]),
        ]);

        $spots = SpotData::getTestData(
            2,
            ['spotID' => 1, 'start' => '08:00:00', 'end' => '08:29:00', 'blockReason' => PestRoutesBlockedSpotReasons::CALENDAR_EVENT_MARKER, 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
            ['spotID' => 2, 'start' => '09:00:00', 'end' => '09:29:00', 'blockReason' => PestRoutesBlockedSpotReasons::CALENDAR_EVENT_MARKER, 'assignedTech' => TestValue::EMPLOYEE1_ID, 'spotCapacity' => 0],
        );

        $this->setUpMockEventsQuery($events);
        $this->setUpMockSpotsDataProcessor($spots, true);

        $this->mockSpotsDataProcessor
            ->shouldNotReceive('blockMultiple');

        $this->action->execute($this->office, $this->date);
    }

    /**
     * @test
     */
    public function it_does_not_include_spots_ending_exactly_at_event_end_time(): void
    {
        $location = new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE);
        $events = collect([
            $this->createEvent($this->date->clone()->hour(10), $this->date->clone()->hour(11), [TestValue::EMPLOYEE1_ID], $location),
        ]);

        $spots = SpotData::getTestData(
            4,
            ['spotID' => 1, 'start' => '09:30:00', 'end' => '10:00:00', 'assignedTech' => TestValue::EMPLOYEE1_ID],
            ['spotID' => 2, 'start' => '10:00:00', 'end' => '10:30:00', 'assignedTech' => TestValue::EMPLOYEE1_ID],
            ['spotID' => 3, 'start' => '10:30:00', 'end' => '11:00:00', 'assignedTech' => TestValue::EMPLOYEE1_ID],
            ['spotID' => 4, 'start' => '11:00:00', 'end' => '11:30:00', 'assignedTech' => TestValue::EMPLOYEE1_ID]
        );

        $this->setUpMockEventsQuery($events);
        $this->setUpMockSpotsDataProcessor($spots);

        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->with(
                $this->office->getId(),
                Mockery::on(function (Collection $spots) {
                    return $spots->count() === 2 && $spots->contains(fn ($spot) => $spot->id === 2) && $spots->contains(fn ($spot) => $spot->id === 3);
                }),
                Mockery::on(function ($description) use ($events) {
                    $event = $events->first();

                    return $this->assertSpotBlockReason($description, $event, $event->getLocation());
                })
            );

        $this->action->execute($this->office, $this->date);
    }

    private function createEvent(CarbonInterface $start, CarbonInterface $end, array $participantIds, $location = null)
    {
        $eventData = [
            'startDate' => $start,
            'endDate' => $end,
            'title' => 'Test Event',
            'description' => 'Description for test event',
            'timeZone' => CarbonTimeZone::create(TestValue::CUSTOMER_TIME_ZONE),
            'startTime' => $start->format('H:i:s'),
            'endTime' => $end->format('H:i:s'),
            'participantIds' => collect($participantIds),
            'location' => $location,
        ];

        return RecurringEventFactory::make($eventData);
    }

    private function assertSpotBlockReason($description, $event, $location)
    {
        $expectedLatitude = round($location->getLatitude(), 5);
        $expectedLongitude = round($location->getLongitude(), 5);

        preg_match('/\[(\-?\d+\.\d+),\s*(\-?\d+\.\d+)\]$/', $description, $matches);
        $actualLatitude = isset($matches[1]) ? (float) $matches[1] : null;
        $actualLongitude = isset($matches[2]) ? (float) $matches[2] : null;

        $matches = [
            'title' => strpos($description, $event->getTitle()) !== false,
            'marker' => stripos($description, PestRoutesBlockedSpotReasons::CALENDAR_EVENT_MARKER) !== false,
            'latitude' => abs($actualLatitude - $expectedLatitude) < 0.001,
            'longitude' => abs($actualLongitude - $expectedLongitude) < 0.001,
        ];

        return $matches['title'] && $matches['marker'] && $matches['latitude'] && $matches['longitude'];
    }

    private function setUpMockEventsQuery(Collection $events)
    {
        $this->mockEventsQuery
            ->shouldReceive('get')
            ->once()
            ->with($this->office->getId(), $this->date)
            ->andReturn($events);
    }

    private function setUpMockSpotsDataProcessor(Collection $spots, bool $shouldUnblock = false)
    {
        $date = DateFilter::between(
            $this->date->clone()->startOfDay()->toDateTime(),
            $this->date->clone()->endOfDay()->toDateTime()
        );

        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSpotsParams $params) use ($date) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['date'] == $date;
            })
            ->andReturn($spots);

        if ($shouldUnblock) {
            $this->mockSpotsDataProcessor
                ->shouldReceive('unblockMultiple')
                ->once()
                ->with($this->office->getId(), Mockery::on(function (Collection $unblockedSpots) use ($spots) {
                    return $unblockedSpots->count() === $spots->count();
                }));
        } else {
            $this->mockSpotsDataProcessor
                ->shouldNotReceive('unblockMultiple');
        }
    }
}
