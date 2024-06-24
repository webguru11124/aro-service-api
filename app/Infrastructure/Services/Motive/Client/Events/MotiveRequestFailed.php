<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Events;

use App\Application\Events\FailedEvent;
use Throwable;

class MotiveRequestFailed extends AbstractMotiveEvent implements FailedEvent
{
    /**
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $options
     * @param Throwable $exception
     */
    public function __construct(
        string $method,
        string $url,
        array $options,
        private readonly Throwable $exception
    ) {
        parent::__construct($method, $url, $options);
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }
}
