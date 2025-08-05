<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\HttpClient;

use App\Infrastructure\Services\Motive\Client\CredentialsRepository;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestFailed;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestSent;
use App\Infrastructure\Services\Motive\Client\Events\MotiveResponseReceived;
use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\Resources\HttpParams;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LaravelHttpClient implements HttpClient
{
    public function __construct(
        private readonly CredentialsRepository $credentialsRepository,
    ) {
    }

    /**
     * @param string $endpoint
     * @param HttpParams|null $params
     * @param array<string, mixed> $headers
     *
     * @return mixed
     * @throws MotiveClientException
     */
    public function get(string $endpoint, HttpParams|null $params = null, array $headers = []): mixed
    {
        $options = [
            'headers' => $this->getHeaders($headers),
        ];

        if ($params !== null) {
            $options['query'] = $this->normalizeQuery(http_build_query($params->toArray()));
        }

        return $this->send('GET', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param HttpParams|null $params
     * @param array<string, mixed> $headers
     *
     * @return mixed
     * @throws MotiveClientException
     */
    public function post(string $endpoint, HttpParams|null $params = null, array $headers = []): mixed
    {
        $options = [
            'headers' => $this->getHeaders($headers),
            'json' => $params?->toArray(),
        ];

        return $this->send('POST', $endpoint, $options);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $options
     *
     * @return mixed
     * @throws MotiveClientException
     */
    private function send(string $method, string $url, array $options): mixed
    {
        try {
            MotiveRequestSent::dispatch($method, $url, $options);

            $response = Http::send($method, $url, $options);

            $this->validateResponse($response);

            $body = $response->body();

            MotiveResponseReceived::dispatch($method, $url, $options, $response->toPsrResponse());

        } catch (\Throwable $exception) {
            MotiveRequestFailed::dispatch($method, $url, $options, $exception);

            throw new MotiveClientException($exception->getMessage());
        }

        return json_decode($body);
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    private function getHeaders(array $headers = []): array
    {
        return array_merge($headers, [self::AUTHENTICATION_HEADER => $this->credentialsRepository->getApiKey()]);
    }

    /**
     * @throws RequestException
     */
    private function validateResponse(Response $response): void
    {
        if (!$response->successful()) {
            $response->throw();
        }
    }

    /**
     * It replaces indexed array query params with non-indexed. e.g.:
     * param%5B0*%5D=value1&param%5B1*%5D=value2 will be transformed to param[]=value1&param[]=value2
     */
    private function normalizeQuery(string $query): string
    {
        return preg_replace('/%5B[0-9]*%5D/', '[]', $query);
    }
}
