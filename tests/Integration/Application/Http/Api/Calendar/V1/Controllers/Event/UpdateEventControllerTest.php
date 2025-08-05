<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Event;

use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\JWTAuthTokenHelper;

class UpdateEventControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_PATCH_EVENT = 'calendar.events.update';
    private const EVENT_ID = 1000000;

    private CalendarEventRepository $calendarEventRepository;

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];

        $this->seed(CalendarSeeder::class);
    }

    /**
     * @test
     *
     * @dataProvider eventDataProvider
     */
    public function it_updates_event_correctly(array $eventData): void
    {
        $response = $this
            ->withHeaders($this->getHeaders())
            ->patch(route(self::ROUTE_PATCH_EVENT, ['event_id' => self::EVENT_ID]), $eventData);

        $response->assertOk();
        $response->assertJsonPath('_metadata.success', true);

        $this->assertDatabaseHas('field_operations.office_days_schedule', [
            'id' => self::EVENT_ID,
            'title' => $eventData['title'],
            'description' => $eventData['description'],
            'location' => json_encode([
                'lat' => $eventData['location_lat'],
                'lon' => $eventData['location_lng'],
            ]),
            'meeting_link' => $eventData['meeting_link'],
            'address' => json_encode([
                'address' => $eventData['address'],
                'city' => $eventData['city'],
                'state' => $eventData['state'],
                'zip' => $eventData['zip'],
            ]),
        ]);
    }

    /**
     * @test
     */
    public function it_throws_404_when_event_not_found(): void
    {
        $notExistingEventId = 500;
        $response = $this
            ->withHeaders($this->getHeaders())
            ->patch(route(self::ROUTE_PATCH_EVENT, ['event_id' => $notExistingEventId]), $this->eventDataProvider()['default update'][0]);

        $response->assertNotFound();
    }

    public static function eventDataProvider(): array
    {
        return [
            'default update' => [
                [
                    'title' => CalendarSeeder::DATA_EVENTS['title'][0] . ' Updated',
                    'description' => CalendarSeeder::DATA_EVENTS['description'][0] . ' Updated',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                    'meeting_link' => 'https://meet.google.com/1234567890',
                    'address' => 'New address',
                    'city' => 'New city',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'empty fields' => [
                [
                    'title' => CalendarSeeder::DATA_EVENTS['title'][0] . ' Updated',
                    'description' => '',
                    'location_lat' => 1.0,
                    'location_lng' => 1.0,
                    'meeting_link' => 'https://meet.google.com/1234567890',
                    'address' => '',
                    'city' => '',
                    'state' => '',
                    'zip' => '',
                ],
            ],
        ];
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
