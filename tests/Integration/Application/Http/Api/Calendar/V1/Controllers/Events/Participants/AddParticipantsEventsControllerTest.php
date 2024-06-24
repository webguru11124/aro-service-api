<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events\Participants;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class AddParticipantsEventsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_ADD_PARTICIPANTS = 'calendar.events.participants.create';

    private PestRoutesEmployeesDataProcessor|MockInterface $pestRoutesEmployeesDataProcessor;

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];

        $this->pestRoutesEmployeesDataProcessor = Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->instance(PestRoutesEmployeesDataProcessor::class, $this->pestRoutesEmployeesDataProcessor);
    }

    /**
     * @test
     */
    public function it_adds_participants_to_event(): void
    {
        $this->pestRoutesEmployeesDataProcessor
            ->shouldReceive('extract')
            ->andReturn(collect());

        $this->seed(CalendarSeeder::class);

        $eventId = CalendarSeeder::DATA_EVENTS['id'][0];
        $participants = [
            $this->faker->randomNumber(5),
            $this->faker->randomNumber(5),
        ];

        $response = $this
            ->withHeaders($this->getHeaders())
            ->put(
                route(self::ROUTE_ADD_PARTICIPANTS, ['event_id' => $eventId]),
                [
                    'participant_ids' => $participants,
                ]
            );

        $response->assertOk();
    }

    /**
     * @test
     */
    public function action_add_participants_fails_with_invalid_request_data(): void
    {
        $response = $this
            ->withHeaders($this->getHeaders())
            ->put(
                route(self::ROUTE_ADD_PARTICIPANTS, ['event_id' => 0]),
                [
                    'participant_ids' => [0],
                ]
            );

        $response->assertBadRequest();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', 'Request validation error.');
    }

    /**
     * @test
     */
    public function action_add_participants_fails_if_no_event_found(): void
    {
        $eventId = TestValue::EVENT_ID;

        $response = $this
            ->withHeaders($this->getHeaders())
            ->put(
                route(self::ROUTE_ADD_PARTICIPANTS, ['event_id' => $eventId]),
                [
                    'participant_ids' => [$this->faker->randomNumber(8)],
                ]
            );

        // Assert the response status is 404 (Not Found)
        $response->assertNotFound();

        // Assert the structure and content of the response JSON
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', "Calendar event with ID [$eventId] not found.");
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
