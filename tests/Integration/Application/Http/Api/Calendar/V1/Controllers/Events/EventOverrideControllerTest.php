<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Application\Services\Calendar\CalendarService;
use App\Domain\Calendar\Exceptions\OverrideOutOfEventRecurrenceException;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class EventOverrideControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_PUT_OVERRIDE = 'calendar.events.overrides.update';

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];
    }

    /**
     * @test
     */
    public function it_return_200_response_when_adds_override_to_event(): void
    {
        $eventId = TestValue::EVENT_ID;

        DB::table('field_operations.office_days_schedule')->insert([
            'id' => $eventId,
            'title' => 'Test title',
            'description' => 'Test description',
            'office_id' => 3,
            'start_date' => '2024-01-01',
            'end_date' => '2024-04-30',
            'start_time' => '08:00:00',
            'end_time' => '08:30:00',
            'time_zone' => 'UTC',
            'location' => '{"lat": 30.1234, "lon": -70.1234}',
            'interval' => 'weekly',
            'occurrence' => 'monday',
            'meeting_link' => 'meeting link',
            'address' => '{"address":"addr","city":"city","state":"LA","zip":"66666"}',
        ]);

        $override = [
            'date' => '2024-01-01',
            'start_time' => '08:00:00',
            'end_time' => '08:30:00',
            'title' => 'Test title',
            'description' => 'Test description',
            'is_canceled' => false,
            'meeting_link' => 'new meeting link',
            'address' => 'new addr',
            'city' => 'city',
            'state' => 'LA',
            'zip' => '66666',
        ];

        $response = $this
            ->withHeaders($this->getHeaders())
            ->putJson(route(self::ROUTE_PUT_OVERRIDE, ['event_id' => $eventId]), $override);

        $response->assertOk();
        $this->assertDatabaseHas(PostgresDBInfo::OFFICE_DAYS_SCHEDULE_OVERRIDES, [
            'schedule_id' => $eventId,
            'date' => $override['date'],
            'start_time' => $override['start_time'],
            'end_time' => $override['end_time'],
            'title' => $override['title'],
            'description' => $override['description'],
            'is_canceled' => $override['is_canceled'],
            'meeting_link' => $override['meeting_link'],
            'address' => '{"address":"new addr","city":"city","state":"LA","zip":"66666"}',
        ]);
    }

    /**
     * @test
     */
    public function it_return_400_response_when_adds_override_bad_request(): void
    {
        $this->seed(CalendarSeeder::class);

        $eventId = CalendarSeeder::DATA_EVENTS['id'][0];
        $override = [
            'title' => 'some title',
        ];

        $response = $this
            ->withHeaders($this->getHeaders())
            ->putJson(route(self::ROUTE_PUT_OVERRIDE, ['event_id' => $eventId]), $override);

        $response->assertBadRequest();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', 'Request validation error.');
    }

    /**
     * @test
     */
    public function add_override_returns_400_if_override_out_of_recurrence(): void
    {
        $this->seed(CalendarSeeder::class);

        $override = [
            'date' => '2023-01-01',
            'start_time' => '08:00:00',
            'end_time' => '08:30:00',
            'title' => 'Test title',
            'description' => 'Test description',
            'is_canceled' => false,
            'meeting_link' => 'new meeting link',
            'address' => 'new addr',
            'city' => 'city',
            'state' => 'LA',
            'zip' => '66666',
        ];

        $calendarServiceMock = Mockery::mock(CalendarService::class);
        $this->instance(CalendarService::class, $calendarServiceMock);
        $calendarServiceMock
            ->shouldReceive('updateCalendarEventOverride')
            ->andThrow(OverrideOutOfEventRecurrenceException::instance());

        $response = $this
            ->withHeaders($this->getHeaders())
            ->putJson(route(self::ROUTE_PUT_OVERRIDE, ['event_id' => CalendarSeeder::DATA_EVENTS['id'][0]]), $override);

        $response->assertBadRequest();
    }

    /**
     * @test
     */
    public function it_returns_404_response_when_add_override_non_exist_event(): void
    {
        $eventId = TestValue::EVENT_ID;

        $override = [
            'date' => '2024-01-01',
            'start_time' => '08:00:00',
            'end_time' => '08:30:00',
            'title' => 'Test title',
            'description' => 'Test description',
            'is_canceled' => false,
            'location_lat' => 46.6343,
            'location_lng' => -71.2346,
        ];

        $response = $this
            ->withHeaders($this->getHeaders())
            ->putJson(route(self::ROUTE_PUT_OVERRIDE, ['event_id' => $eventId]), $override);

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', "Calendar event with ID [$eventId] not found.");
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
