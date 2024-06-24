<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Application\Events\PestRoutesRequestRetry;
use Aptive\PestRoutesSDK\Client as PestroutesClient;
use Aptive\PestRoutesSDK\DynamoDbCredentialsRepository;
use DateTime;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use GuzzleHttp\{Client as GuzzleClient, HandlerStack, Middleware, RetryMiddleware};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PestroutesSdkClientProvider extends ServiceProvider
{
    private const int MAX_RETRIES = 3;
    private const array RETRY_CODES = [429, 500];

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        // Bind PestroutesSdkClient
        $this->app->singleton(PestroutesClient::class, function () {
            return new PestroutesClient(
                config('pestroutes-sdk.url'),
                new DynamoDbCredentialsRepository(config('pestroutes-sdk.credentials.dynamo_db_table')),
                Log::getLogger(),
                $this->getGuzzleClientWithRetryMiddleware()
            );
        });
    }

    private function getGuzzleClientWithRetryMiddleware(): GuzzleClient
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(
            function (int $retries, RequestInterface $request, ResponseInterface $response = null): bool {
                return
                    $retries < self::MAX_RETRIES
                    && $response !== null
                    && in_array($response->getStatusCode(), self::RETRY_CODES);
            },
            function (int $retries, ResponseInterface $response): int {
                PestRoutesRequestRetry::dispatch($retries, $response->getStatusCode());

                if (!$response->hasHeader('Retry-After')) {
                    return RetryMiddleware::exponentialDelay($retries);
                }

                $retryAfter = $response->getHeaderLine('Retry-After');

                if (!is_numeric($retryAfter)) {
                    $retryAfter = (new DateTime($retryAfter))->getTimestamp() - time();
                }

                return (int) $retryAfter * 1000;
            }
        ));

        return new GuzzleClient(['handler' => $stack]);
    }
}
