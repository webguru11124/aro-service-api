<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\HttpClient;

use App\Infrastructure\Services\Motive\Client\Resources\HttpParams;

interface HttpClient
{
    public const AUTHENTICATION_HEADER = 'X-API-KEY';

    /**
     * @param string $endpoint
     * @param HttpParams|null $params
     * @param array<string, mixed> $headers
     *
     * @return mixed
     */
    public function get(string $endpoint, HttpParams|null $params = null, array $headers = []): mixed;

    /**
     * @param string $endpoint
     * @param HttpParams|null $params
     * @param array<string, mixed> $headers
     *
     * @return mixed
     */
    public function post(string $endpoint, HttpParams|null $params = null, array $headers = []): mixed;
}
