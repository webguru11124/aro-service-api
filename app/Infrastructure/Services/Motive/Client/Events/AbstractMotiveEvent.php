<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AbstractMotiveEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly string $method,
        private readonly string $url,
        private readonly array $options,
    ) {
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
