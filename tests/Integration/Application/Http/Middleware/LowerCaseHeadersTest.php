<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Middleware;

use App\Application\Http\Middleware\LowerCaseHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class LowerCaseHeadersTest extends TestCase
{
    private $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LowerCaseHeaders();
    }

    /**
     * @test
     */
    public function it_transforms_headers_keys_to_lowercase(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'Custom Value',
            'ACCEPT-LANGUAGE' => 'en-US',
        ];

        $transformedHeaders = $this->middleware->transformHeaders($headers);

        $this->assertEquals([
            'content-type' => 'application/json',
            'x-custom-header' => 'Custom Value',
            'accept-language' => 'en-US',
        ], $transformedHeaders);
    }

    /**
     * @test
     */
    public function it_transforms_request_headers_to_lowercase(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->add(['Content-Type' => 'application/json']);
        $middleware = new LowerCaseHeaders();

        $transformedRequest = $request;

        $middleware->handle($request, function ($request) use (&$transformedRequest) {
            $transformedRequest = $request;
            $response = new Response();

            return $response;
        });

        $this->assertArrayHasKey('content-type', $transformedRequest->headers->all());
        $this->assertArrayNotHasKey('Content-Type', $transformedRequest->headers->all());
    }

    /**
     * @test
     */
    public function it_transforms_response_headers_to_lowercase(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response();
        $response->header('X-CUSTOM-HEADER', 'Custom Value');
        $middleware = new LowerCaseHeaders();

        $transformedResponse = $middleware->handle($request, function ($request) use ($response) {
            return $response;
        });

        $this->assertArrayHasKey('x-custom-header', $transformedResponse->headers->all());
        $this->assertArrayNotHasKey('X-CUSTOM-HEADER', $transformedResponse->headers->all());
    }
}
