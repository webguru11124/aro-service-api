<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class DeleteEventControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_DELETE_BY_ID = 'calendar.events.delete';

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
    public function it_returns_200_response_when_office_day_event_deleted_successfully(): void
    {
        $this->seed(CalendarSeeder::class);

        $eventId = CalendarSeeder::DATA_EVENTS['id'][0];

        $uri = route(self::ROUTE_DELETE_BY_ID, ['event_id' => $eventId]);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->delete($uri);

        $response->assertOk();
    }

    /**
     * @test
     */
    public function it_returns_404_response_when_event_not_found(): void
    {
        $this->seed(CalendarSeeder::class);

        $eventId = TestValue::EVENT_ID;

        $uri = route(self::ROUTE_DELETE_BY_ID, ['event_id' => $eventId]);
        $response = $this
            ->withHeaders($this->getHeaders())
            ->delete($uri);

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', "Calendar event with ID [$eventId] not found.");
    }

    /**
     * @return mixed[]
     */
    private function getHeaders(): array
    {
        return $this->headers;
    }
}
