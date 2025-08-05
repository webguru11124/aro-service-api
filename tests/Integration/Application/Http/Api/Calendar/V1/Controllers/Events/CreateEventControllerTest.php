<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Domain\Calendar\Enums\EventType;
use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class CreateEventControllerTest extends TestCase
{
    use DatabaseTransactions;
    use JWTAuthTokenHelper;

    private const ROUTE_CREATE = 'calendar.events.create';

    private const VALID_DATA = [
        'office_id' => TestValue::OFFICE_ID,
        'title' => 'Test title',
        'description' => 'Test description',
        'event_type' => EventType::MEETING->value,
        'start_date' => '2024-01-01',
        'end_date' => '2024-04-30',
        'start_at' => '08:00:00',
        'end_at' => '08:30:00',
        'interval' => 'monthly',
        'week_day' => null,
        'location_lat' => 46.6343,
        'location_lng' => -71.2346,
        'meeting_link' => 'https://meet.google.com/xxx-xxx-xxx',
        'address' => 'address',
        'city' => 'city',
        'state' => 'LA',
        'zip' => '12345',
        'end_after' => 'date',
        'week_num' => 1,
    ];

    private OfficeQuery|MockInterface $officeQueryMock;

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];

        $this->officeQueryMock = Mockery::mock(OfficeQuery::class);
        $this->instance(OfficeQuery::class, $this->officeQueryMock);
    }

    /**
     * @test
     */
    public function action_create_returns_201_when_new_event_created(): void
    {
        $this->officeQueryMock
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->once()
            ->andReturn(OfficeFactory::make());

        $response = $this
            ->withHeaders($this->getHeaders())
            ->postJson(route(self::ROUTE_CREATE), self::VALID_DATA);

        $response->assertCreated();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
            'result' => ['message'],
        ]);
    }

    /**
     * @test
     */
    public function it_creates_an_event_with_empty_address(): void
    {
        $this->officeQueryMock
            ->shouldReceive('get')
            ->andReturn(OfficeFactory::make());

        $response = $this
            ->withHeaders($this->getHeaders())
            ->postJson(route(self::ROUTE_CREATE), array_diff_key(self::VALID_DATA, [
                'address' => 'address',
                'city' => 'city',
                'state' => 'LA',
                'zip' => '12345',
            ]));

        $response->assertCreated();

        $this->assertDatabaseHas(PostgresDBInfo::OFFICE_DAYS_SCHEDULE, [
            'title' => self::VALID_DATA['title'],
            'description' => self::VALID_DATA['description'],
            'address' => null,
        ]);
    }

    /**
     * @test
     */
    public function actions_create_returns_400_response_when_invalid_parameter_given(): void
    {
        $interval = $this->faker->text(6);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->postJson(route(self::ROUTE_CREATE), array_merge(self::VALID_DATA, ['interval' => $interval]));

        $response->assertBadRequest();
        $response->assertJsonPath('_metadata.success', false);
    }

    /**
     * @test
     */
    public function action_create_return_404_when_non_existing_office_id_provided(): void
    {
        $officeId = $this->faker->randomNumber(4);

        $this->officeQueryMock
            ->shouldReceive('get')
            ->with($officeId)
            ->once()
            ->andThrow(OfficeNotFoundException::class);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->postJson(route(self::ROUTE_CREATE), array_merge(self::VALID_DATA, ['office_id' => $officeId]));

        $response->assertNotFound();
    }

    /**
     * @return mixed[]
     */
    private function getHeaders(): array
    {
        return $this->headers;
    }
}
