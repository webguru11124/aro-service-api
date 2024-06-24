<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Employee;

use App\Application\Services\Calendar\CalendarAvatarService;
use Mockery;
use Tests\TestCase;
use Tests\Tools\JWTAuthTokenHelper;
use Tests\Tools\TestValue;

class GetAvatarControllerTest extends TestCase
{
    use JWTAuthTokenHelper;

    private const ROUTE_GET_EMPLOYEE_AVATAR = 'calendar.avatars.index';

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
    public function it_returns_an_avatar_of_the_employee(): void
    {
        $calendarAvatarServiceMock = Mockery::mock(CalendarAvatarService::class);
        $this->instance(CalendarAvatarService::class, $calendarAvatarServiceMock);
        $calendarAvatarServiceMock
            ->shouldReceive('getAvatar')
            ->with(TestValue::WORKDAY_ID)
            ->once()
            ->andReturn('avatar');

        $response = $this
            ->withHeaders($this->getHeaders())
            ->get(route(self::ROUTE_GET_EMPLOYEE_AVATAR, ['external_id' => TestValue::WORKDAY_ID]));

        $response->assertOk();
        $response->assertJsonPath('_metadata.success', true);
    }

    /**
     * @test
     */
    public function it_returns_404_if_avatar_not_found(): void
    {
        $calendarAvatarServiceMock = Mockery::mock(CalendarAvatarService::class);
        $this->instance(CalendarAvatarService::class, $calendarAvatarServiceMock);
        $calendarAvatarServiceMock
            ->shouldReceive('getAvatar')
            ->with(TestValue::WORKDAY_ID)
            ->once()
            ->andReturn('');

        $response = $this
            ->withHeaders($this->getHeaders())
            ->get(route(self::ROUTE_GET_EMPLOYEE_AVATAR, ['external_id' => TestValue::WORKDAY_ID]));

        $response->assertNotFound();
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
