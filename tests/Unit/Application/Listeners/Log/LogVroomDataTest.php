<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Listeners\Log;

use App\Application\Events\Vroom\VroomRequestSent;
use App\Application\Events\Vroom\VroomResponseReceived;
use App\Application\Listeners\Log\LogVroomData;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use Aptive\Component\Http\HttpStatus;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Tools\Factories\Vroom\JobFactory;
use Tests\Tools\Factories\Vroom\VehicleFactory;

class LogVroomDataTest extends TestCase
{
    private LogVroomData $listener;
    private string $requestId;
    private string $url;
    private VroomInputData $vroomInputData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new LogVroomData();

        $this->requestId = uniqid();
        $this->url = 'https://vroom.url';
        $this->vroomInputData = new VroomInputData(
            new Collection(VehicleFactory::many(1)),
            new Collection(JobFactory::many(3)),
            new Collection(),
        );
    }

    /**
     * @test
     */
    public function it_listens_events(): void
    {
        Event::fake();

        Event::assertListening(
            VroomRequestSent::class,
            LogVroomData::class
        );

        Event::assertListening(
            VroomResponseReceived::class,
            LogVroomData::class
        );
    }

    /**
     * @test
     */
    public function it_logs_request(): void
    {
        $dateString = $this->faker->date();
        $date = Carbon::parse($dateString);
        $officeId = $this->faker->randomNumber(2);

        $event = new VroomRequestSent($this->requestId, $this->url, $date, $officeId, $this->vroomInputData);

        Log::expects('info')
            ->withSomeOfArgs('Vroom Request.' . " Date: $dateString" . ". Office: $officeId");

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_success_response(): void
    {
        $response = \Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturnTrue();
        $response->shouldReceive('body')->andReturn('[]');
        $response->shouldReceive('status')->andReturn(HttpStatus::OK);

        $dateString = $this->faker->date();
        $date = Carbon::parse($dateString);
        $officeId = $this->faker->randomNumber(2);

        $event = new VroomResponseReceived($this->requestId, $date, $officeId, $response);

        Log::expects('log')
            ->withSomeOfArgs('info', 'Vroom Response.' . " Date: $dateString" . ". Office: $officeId");

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_logs_failed_response(): void
    {
        $response = \Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturnFalse();
        $response->shouldReceive('body')->andReturn('[]');
        $response->shouldReceive('status')->andReturn(HttpStatus::BAD_REQUEST);

        $dateString = $this->faker->date();
        $date = Carbon::parse($dateString);
        $officeId = $this->faker->randomNumber(2);

        $event = new VroomResponseReceived($this->requestId, $date, $officeId, $response);

        Log::expects('log')
            ->withSomeOfArgs('error', 'Vroom Response.' . " Date: $dateString" . ". Office: $officeId");

        $this->listener->handle($event);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->listener);
        unset($this->requestId);
        unset($this->url);
    }
}
