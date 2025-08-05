<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events\Participants;

use App\Application\Services\Calendar\CalendarEventParticipantsService;
use App\Domain\Calendar\Entities\Participant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class GetParticipantsEventsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_GET_PARTICIPANTS = 'calendar.events.participants.index';

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
    public function it_returns_list_of_employees_available_for_office_and_marks_invited_employees(): void
    {
        $calendarParticipantsService = Mockery::mock(CalendarEventParticipantsService::class);
        $this->instance(CalendarEventParticipantsService::class, $calendarParticipantsService);

        $calendarParticipantsService
            ->shouldReceive('getParticipants')
            ->once()
            ->andReturn(collect([
                new Participant(
                    TestValue::EMPLOYEE1_ID,
                    'John Doe',
                    true,
                ),
                new Participant(
                    TestValue::EMPLOYEE2_ID,
                    'Jane Doe',
                    false,
                ),
            ]));

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

        DB::table('field_operations.office_days_participants')->insert([
            'schedule_id' => $eventId,
            'employee_id' => TestValue::EMPLOYEE1_ID,
        ]);

        $uri = route(self::ROUTE_GET_PARTICIPANTS, ['event_id' => $eventId]);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->get($uri);

        $response->assertOk();
        $response->assertJsonPath('_metadata.success', true);
        $response->assertJsonPath('result.participants.0.id', TestValue::EMPLOYEE1_ID);
        $response->assertJsonPath('result.participants.0.is_invited', true);
        $response->assertJsonPath('result.participants.1.id', TestValue::EMPLOYEE2_ID);
        $response->assertJsonPath('result.participants.1.is_invited', false);
    }

    /**
     * @test
     */
    public function get_participants_returns_404_if_event_not_found(): void
    {
        $response = $this
            ->withHeaders($this->getHeaders())
            ->get(route(self::ROUTE_GET_PARTICIPANTS, ['event_id' => TestValue::EVENT_ID]));

        $response->assertNotFound();
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
