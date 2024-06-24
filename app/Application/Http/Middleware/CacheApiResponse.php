<?php

declare(strict_types=1);

namespace App\Application\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Closure;

class CacheApiResponse
{
    private const HASH_ALGO = 'md5';
    private const DEFAULT_CACHE_TTL = 60;
    private const CACHE_PREFIX = 'ApiResponse_';

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, int $cacheTtl = self::DEFAULT_CACHE_TTL): mixed
    {
        $etag = $this->buildKey($request);

        if (!$request->isNoCache() && Cache::has($etag)) {
            $cachedResponse = Cache::get($etag);
            $age = now()->diffInSeconds(Carbon::createFromTimestamp($cachedResponse['timestamp']));

            return response()->json(json_decode($cachedResponse['content'], true))
                ->setCache(['max_age' => $cacheTtl, 'etag' => $etag])
                ->withHeaders(['Age' => $age]);
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            $this->cacheResponse($etag, $response, now()->timestamp);
            $response
                ->setCache(['max_age' => $cacheTtl, 'etag' => $etag])
                ->withHeaders(['Age' => 0]);
        }

        return $response;
    }

    protected function cacheResponse(string $cacheKey, mixed $response, int $timestamp): void
    {
        Cache::put($cacheKey, [
            'content' => $response->getContent(),
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function buildKey(Request $request): string
    {
        $urlPath = $request->path();
        $queryParams = $this->getQueryParams($request);
        $requestMethod = $request->method();

        return self::CACHE_PREFIX . hash(self::HASH_ALGO, $requestMethod . $urlPath . http_build_query($queryParams));
    }

    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    protected function getQueryParams(Request $request): array
    {
        $queryParams = $request->query();

        if (empty($queryParams)) {
            return [];
        } else {
            ksort($queryParams);

            return $queryParams;
        }
    }
}
