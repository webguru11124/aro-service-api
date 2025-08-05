<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Events;

use Psr\Http\Message\ResponseInterface;

class MotiveResponseReceived extends AbstractMotiveEvent
{
    /**
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $options
     * @param ResponseInterface $response
     */
    public function __construct(
        string $method,
        string $url,
        array $options,
        private readonly ResponseInterface $response
    ) {
        parent::__construct($method, $url, $options);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
