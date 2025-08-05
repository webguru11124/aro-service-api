<?php

declare(strict_types=1);

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LowerCaseHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->replace($this->transformHeaders($request->headers->all()));
        $response = $next($request);
        $response->headers->replace($this->transformHeaders($response->headers->all()));

        return $response;
    }

    /**
     * @param array<string, array<string>> $headers
     *
     * @return array<string, array<string>>
     */
    public function transformHeaders(array $headers): array
    {
        $lowercaseHeaders = [];

        foreach ($headers as $name => $values) {
            $lowercaseHeaders[strtolower($name)] = $values;
        }

        return $lowercaseHeaders;
    }
}
