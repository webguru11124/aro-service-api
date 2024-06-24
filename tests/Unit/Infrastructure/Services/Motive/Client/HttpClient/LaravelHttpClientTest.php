<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\HttpClient;

use App\Infrastructure\Services\Motive\Client\CredentialsRepository;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestFailed;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestSent;
use App\Infrastructure\Services\Motive\Client\Events\MotiveResponseReceived;
use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\HttpClient\LaravelHttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use Aptive\Component\Http\HttpStatus;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class LaravelHttpClientTest extends TestCase
{
    private const TEST_API_KEY = 'testapikey';
    private const TEST_ENDPOINT = 'https://endpoint.com/v1/test';

    private CredentialsRepository|MockInterface $credentialsRepositoryMock;
    private LaravelHttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsRepositoryMock = \Mockery::mock(CredentialsRepository::class);

        $this->client = new LaravelHttpClient(
            $this->credentialsRepositoryMock,
        );

        Http::fake();
    }

    /**
     * @test
     */
    public function it_sends_get_request(): void
    {
        $paramName = 'p1';
        $paramValue = 'v1';
        $arrayParamName = 'a';
        $arrayParamValue1 = 'av1';
        $arrayParamValue2 = 'av2';

        $params = [
            $paramName => $paramValue,
            $arrayParamName => [
                $arrayParamValue1,
                $arrayParamValue2,
            ],
        ];

        $headerName = 'X-Api-Test';
        $headerValue = 'TestHeader';
        $headers = [$headerName => $headerValue];

        $paramsMock = \Mockery::mock(AbstractHttpParams::class);
        $paramsMock
            ->shouldReceive('toArray')
            ->once()
            ->andReturn($params);

        $this->shouldRequestApiKey(1);

        $object = new \StdClass();
        $object->property = 'value';

        $response = new Response(new GuzzleResponse(body: json_encode($object)));

        $query = "$paramName=$paramValue&$arrayParamName" . '[]' . "=$arrayParamValue1&$arrayParamName" . '[]' . "=$arrayParamValue2";

        Http::shouldReceive('send')
            ->withArgs(function (string $method, string $url, array $options) use ($query, $headerName, $headerValue) {
                $headers = $options['headers'];

                return $method === 'GET'
                    && $options['query'] === $query
                    && $headers['X-API-KEY'] === self::TEST_API_KEY
                    && $headers[$headerName] === $headerValue;
            })
            ->once()
            ->andReturn($response);

        Event::fake();

        $result = $this->client->get(self::TEST_ENDPOINT, $paramsMock, $headers);

        $this->assertEquals($object, $result);
        Event::assertDispatched(MotiveRequestSent::class);
        Event::assertDispatched(MotiveResponseReceived::class);
    }

    /**
     * @test
     */
    public function it_sends_post_request(): void
    {
        $paramName = 'p1';
        $paramValue = 'v1';
        $params = [$paramName => $paramValue];

        $headerName = 'X-Api-Test';
        $headerValue = 'TestHeader';
        $headers = [$headerName => $headerValue];

        $paramsMock = \Mockery::mock(AbstractHttpParams::class);
        $paramsMock
            ->shouldReceive('toArray')
            ->once()
            ->andReturn($params);

        $this->shouldRequestApiKey(1);

        $object = new \StdClass();
        $object->property = 'value';

        $response = new Response(new GuzzleResponse(body: json_encode($object)));

        Http::shouldReceive('send')
            ->withArgs(function (string $method, string $url, array $options) use ($params, $headerName, $headerValue) {
                $headers = $options['headers'];

                return $method === 'POST'
                    && $options['json'] === $params
                    && $headers['X-API-KEY'] === self::TEST_API_KEY
                    && $headers[$headerName] === $headerValue;
            })
            ->once()
            ->andReturn($response);

        Event::fake();

        $result = $this->client->post(self::TEST_ENDPOINT, $paramsMock, $headers);

        $this->assertEquals($object, $result);
        Event::assertDispatched(MotiveRequestSent::class);
        Event::assertDispatched(MotiveResponseReceived::class);
    }

    /**
     * @test
     */
    public function get_method_throws_exception_if_request_failed(): void
    {
        $this->shouldRequestApiKey(1);

        $guzzleResponse = new GuzzleResponse(
            status: HttpStatus::NOT_FOUND,
            body: json_encode(['error_message' => 'not found'])
        );
        $response = new Response($guzzleResponse);

        Http::shouldReceive('send')->andReturn($response);

        Event::fake();

        $this->expectException(MotiveClientException::class);
        $this->client->get(self::TEST_ENDPOINT);

        Event::assertDispatched(MotiveRequestSent::class);
        Event::assertDispatched(MotiveRequestFailed::class);
    }

    /**
     * @test
     */
    public function post_method_throws_exception_if_request_failed(): void
    {
        $this->shouldRequestApiKey(1);

        $guzzleResponse = new GuzzleResponse(
            status: HttpStatus::NOT_FOUND,
            body: json_encode(['error_message' => 'not found'])
        );
        $response = new Response($guzzleResponse);
        Http::shouldReceive('send')->andReturn($response);

        Event::fake();

        $this->expectException(MotiveClientException::class);
        $this->client->post(self::TEST_ENDPOINT);

        Event::assertDispatched(MotiveRequestSent::class);
        Event::assertDispatched(MotiveRequestFailed::class);
    }

    private function shouldRequestApiKey(int $times): void
    {
        $this->credentialsRepositoryMock
            ->shouldReceive('getApiKey')
            ->times($times)
            ->andReturn(self::TEST_API_KEY);
    }
}
