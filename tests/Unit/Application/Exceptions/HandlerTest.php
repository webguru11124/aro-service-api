<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use App\Application\Exceptions\Handler;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    private Request $stubRequest;
    private Handler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stubRequest = new Request();
        $this->handler = new Handler(Container::getInstance());
    }

    /**
     * @test
     */
    public function it_logs_an_error_and_returns_500_for_any_exception_by_default(): void
    {
        Config::set('app.debug', false);

        $exception = new \Exception('Some Random Exception');
        $expectedResponseBody = [
            '_metadata' => [
                'success' => false,
            ],
            'result' => [
                'message' => 'An error was encountered while attempting to process the request. Please contact the System Administrator.',
            ],
        ];

        Log::shouldReceive('error');

        $response = $this->handler->render($this->stubRequest, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEqualsCanonicalizing($expectedResponseBody, $response->getData(true));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->stubRequest);
        unset($this->handler);
    }
}
