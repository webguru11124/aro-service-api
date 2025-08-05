<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Tools\DatabaseSeeders\CalendarSeeder;
use Tests\Tools\JWTAuthTokenHelper;

class GetEventsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_GET_EVENTS = 'calendar.events.index';

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
    public function it_shows_searched_events_with_pagination(): void
    {
        $this->seed(CalendarSeeder::class);
        $params = $this->getEventsIndexValidParams();

        $response = $this
            ->withHeaders($this->getHeaders())
            ->get(route(self::ROUTE_GET_EVENTS, $params));

        $response->assertOk();
        $response->assertJsonPath('_metadata.success', true);
        $response->assertJsonStructure([
            '_metadata' => ['success', 'pagination' => ['total', 'per_page', 'current_page', 'total_pages']],
            'result' => ['events'],
        ]);
        $response->assertJsonCount($params['per_page'], 'result.events');
    }

    private function getEventsIndexValidParams(): array
    {
        $perPage = 3;

        return [
            'start_date' => CalendarSeeder::DATA_EVENTS['start_date'][0],
            'end_date' => CalendarSeeder::DATA_EVENTS['end_date'][0],
            'office_id' => CalendarSeeder::DATA_EVENTS['office_id'][0],
            'page' => 1,
            'per_page' => $perPage,
        ];
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
