<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Websocket;

use App\Domain\Tracking\Exceptions\FailedPublishTrackingDataException;
use App\Infrastructure\Services\WebsocketTracking\ProvidedServicesTrackingDataFormatter;
use App\Infrastructure\Services\WebsocketTracking\WebsocketTrackingService;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\TreatmentStateFactory;
use Tests\Tools\TestValue;

class WebsocketTrackingServiceTest extends TestCase
{
    private const TEST_API_URL = 'test_service_url';
    private const TEST_ROOM = 'test-room';

    private WebsocketTrackingService $service;
    private ProvidedServicesTrackingDataFormatter|MockInterface $mockFormatter;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'api_url' => self::TEST_API_URL,
            'room' => self::TEST_ROOM,
        ];

        $this->mockFormatter = Mockery::mock(ProvidedServicesTrackingDataFormatter::class);
        $this->service = new WebsocketTrackingService(
            $config,
            $this->mockFormatter
        );
    }

    /**
     * @test
     */
    public function it_publishes_tracking_data(): void
    {
        Http::fake([
            '*' => Http::response(['ok']),
        ]);

        $state = TreatmentStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
        ]);
        $this->mockFormatter
            ->shouldReceive('format')
            ->once()
            ->with($state)
            ->andReturn(['formatted_state' => ['office_id' => $state->getId()->officeId]]);

        $this->service->publish($state);

        $endpoint = self::TEST_API_URL . '/v1/rooms/' . self::TEST_ROOM . '/events/track-office-' . $state->getId()->officeId;

        Http::assertSent(function (Request $request) use ($endpoint) {
            return $request->url() === $endpoint
                && !empty($request['formatted_state']);
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_when_request_failed(): void
    {
        Http::fake([
            '*' => Http::response([], HttpStatus::INTERNAL_SERVER_ERROR),
        ]);

        $state = TreatmentStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
        ]);
        $this->mockFormatter
            ->shouldReceive('format')
            ->once()
            ->with($state)
            ->andReturn(['formatted_state' => ['office_id' => $state->getId()->officeId]]);

        $this->expectException(FailedPublishTrackingDataException::class);

        $this->service->publish($state);
    }
}
