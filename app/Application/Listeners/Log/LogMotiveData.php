<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Infrastructure\Helpers\DataMask;
use App\Infrastructure\Services\Motive\Client\Events\AbstractMotiveEvent;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestFailed;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestSent;
use App\Infrastructure\Services\Motive\Client\Events\MotiveResponseReceived;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class LogMotiveData
{
    private const OPTIONS_FIELDS_TO_MASK = [
        'headers.X-API-KEY',
    ];

    /**
     * Handle the event.
     */
    public function handle(AbstractMotiveEvent $event): void
    {
        match (true) {
            $event instanceof MotiveResponseReceived => $this->logResponse($event),
            $event instanceof MotiveRequestSent => $this->logSentRequest($event),
            $event instanceof MotiveRequestFailed => $this->logFailedRequest($event),
            default => throw new \UnexpectedValueException('Unexpected event type: ' . get_class($event)),
        };
    }

    private function logResponse(MotiveResponseReceived $event): void
    {
        $level = $this->isSuccessful($event->getResponse()) ? 'info' : 'error';
        $responseData = [
            'status_code' => $event->getResponse()->getStatusCode(),
            'headers' => $event->getResponse()->getHeaders(),
            'body' => (string) $event->getResponse()->getBody(),
        ];

        Log::log(
            $level,
            'Motive Received Response',
            [
                'method' => $event->getMethod(),
                'url' => $event->getUrl(),
                'options' => $this->maskOptionsFields($event->getOptions()),
                'response' => $responseData,
            ]
        );
    }

    private function isSuccessful(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    private function logSentRequest(MotiveRequestSent $event): void
    {
        Log::info(
            'Motive Request sent',
            [
                'method' => $event->getMethod(),
                'url' => $event->getUrl(),
                'options' => $this->maskOptionsFields($event->getOptions()),
            ]
        );
    }

    private function logFailedRequest(MotiveRequestFailed $event): void
    {
        Log::error('Motive Request Failed', [
            'method' => $event->getMethod(),
            'url' => $event->getUrl(),
            'options' => $this->maskOptionsFields($event->getOptions()),
            'message' => $event->getException()->getMessage(),
        ]);
    }

    /**
     * @param mixed[] $options
     *
     * @return mixed[]
     */
    private function maskOptionsFields(array $options): array
    {
        return DataMask::mask($options, self::OPTIONS_FIELDS_TO_MASK);
    }
}
