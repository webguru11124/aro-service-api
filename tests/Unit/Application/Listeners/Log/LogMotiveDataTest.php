<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Listeners\Log\LogMotiveData;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestFailed;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestSent;
use App\Infrastructure\Services\Motive\Client\Events\MotiveResponseReceived;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class LogMotiveDataTest extends TestCase
{
    private LogMotiveData $listener;
    private string $method;
    private string $url;
    private array $options;
    private const MASK = '*****';

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogMotiveData();
        $this->setUpFakeData();
    }

    private function setUpFakeData(): void
    {
        $this->method = $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']);
        $this->url = $this->faker->url;
        $this->options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->faker->uuid,
                'Content-Type' => 'application/json',
                'X-API-KEY' => 'test_api_key',
            ],
            'json' => [
                'data' => $this->faker->sentence,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(
            MotiveResponseReceived::class,
            LogMotiveData::class
        );

        Event::assertListening(
            MotiveRequestSent::class,
            LogMotiveData::class
        );

        Event::assertListening(
            MotiveRequestFailed::class,
            LogMotiveData::class
        );
    }

    /**
     * @test
     */
    public function it_logs_succesful_received_response(): void
    {
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(HttpStatus::OK);
        $response->shouldReceive('getHeaders')->andReturn([]);
        $response->shouldReceive('getBody')->andReturn();

        $event = new MotiveResponseReceived($this->method, $this->url, $this->options, $response);

        Log::expects('log')
            ->withSomeOfArgs('info', 'Motive Received Response');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_failed_received_response(): void
    {
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(HttpStatus::BAD_REQUEST);
        $response->shouldReceive('getHeaders')->andReturn([]);
        $response->shouldReceive('getBody')->andReturn();

        $event = new MotiveResponseReceived($this->method, $this->url, $this->options, $response);

        $this->listener->handle($event);

        $event = new MotiveResponseReceived($this->method, $this->url, $this->options, $response);

        Log::expects('log')
            ->withSomeOfArgs('error', 'Motive Received Response');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_sent_request(): void
    {
        $event = new MotiveRequestSent($this->method, $this->url, $this->options);

        Log::expects('info')
            ->withSomeOfArgs('Motive Request sent');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_failed_request(): void
    {
        $exception = new \Exception($this->faker->sentence);
        $event = new MotiveRequestFailed($this->method, $this->url, $this->options, $exception);

        Log::expects('error')
            ->with('Motive Request Failed', \Mockery::on(function ($data) {
                return $data['options']['headers']['X-API-KEY'] === self::MASK;
            }));

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
        unset($this->method);
        unset($this->url);
        unset($this->options);
    }
}
