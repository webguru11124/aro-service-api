<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Middleware;

use App\Application\Http\Middleware\CacheApiResponse;
use Aptive\Component\Http\HttpStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheApiResponseTest extends TestCase
{
    private const ROUTE_NAME = 'tracking.fleet-routes.index';
    private const CACHE_PREFIX = 'ApiResponse_';

    private CacheApiResponse $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CacheApiResponse();
    }

    /**
     * @test
     */
    public function it_caches_successful_responses(): void
    {
        $mockResponseContent = '{"data": "example"}';
        $response = new Response($mockResponseContent, HttpStatus::OK);
        $requestMethod = 'GET';
        $request = Request::create(self::ROUTE_NAME, $requestMethod);
        $middlewareResponse = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });
        $this->assertJson($middlewareResponse->getContent());

        $cacheKey = self::CACHE_PREFIX . md5($requestMethod . self::ROUTE_NAME);
        $cachedResponse = Cache::get($cacheKey);

        $this->assertEquals($mockResponseContent, $cachedResponse['content']);
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * @test
     */
    public function it_does_not_cache_unsuccessful_responses(): void
    {
        $mockResponseContent = 'Error Response';
        $response = new Response($mockResponseContent, random_int(400, 599));
        $request = Request::create(self::ROUTE_NAME, 'GET');
        $cachedResponse = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertEquals($mockResponseContent, $cachedResponse->getContent());
        $this->assertFalse(Cache::has(self::CACHE_PREFIX . md5(self::ROUTE_NAME)));
    }

    /**
     * @test
     */
    public function it_returns_cached_response_before_expiration(): void
    {
        $mockResponseContent = '{"data":"example"}';
        $response = new Response($mockResponseContent, HttpStatus::OK);
        $requestMethod = 'GET';
        $request = Request::create(self::ROUTE_NAME, $requestMethod);
        $cacheKey = self::CACHE_PREFIX . md5($requestMethod . self::ROUTE_NAME);

        $this->middleware->handle($request, function () use ($response) {
            return $response;
        });
        $this->assertTrue(Cache::has($cacheKey));

        $middlewareResponse = $this->middleware->handle($request, function () {
            throw new Exception('It should not be called as the response has just been cached');
        });

        $this->assertTrue(Cache::has($cacheKey));
        $this->assertJson($middlewareResponse->getContent());
        $this->assertEquals($mockResponseContent, $middlewareResponse->getContent());
    }
}
