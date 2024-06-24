<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events\Participants;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class DeleteParticipantsEventsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_DELETE_PARTICIPANT = 'calendar.events.participants.delete';

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
    public function it_removes_participants_from_event(): void
    {
        $this->seed(CalendarSeeder::class);

        $eventId = CalendarSeeder::DATA_EVENTS['id'][0];
        $participantId = CalendarSeeder::DATA_PARTICIPANTS['employee_id'][0];

        $uri = route(self::ROUTE_DELETE_PARTICIPANT, ['event_id' => $eventId, 'participant_id' => $participantId]);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->delete($uri);

        $response->assertOk();
    }

    /**
     * @test
     */
    public function action_remove_participant_fails_if_no_event_found(): void
    {
        $this->seed(CalendarSeeder::class);

        $eventId = TestValue::EVENT_ID;
        $participantId = CalendarSeeder::DATA_PARTICIPANTS['employee_id'][0];

        $uri = route(self::ROUTE_DELETE_PARTICIPANT, ['event_id' => $eventId, 'participant_id' => $participantId]);
        $response = $this
            ->withHeaders($this->getHeaders())
            ->delete($uri);

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', "Calendar event with ID [$eventId] not found.");
    }

    /**
     * @test
     */
    public function action_remove_participant_fails_if_no_participant_found(): void
    {
        $this->seed(CalendarSeeder::class);
        $eventId = CalendarSeeder::DATA_EVENTS['id'][0];
        $participationId = $this->faker->randomNumber(8);

        $uri = route(self::ROUTE_DELETE_PARTICIPANT, ['event_id' => $eventId, 'participant_id' => $participationId]);
        $response = $this
            ->withHeaders($this->getHeaders())
            ->delete($uri);

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', "Participant ID [$participationId] not found.");
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
