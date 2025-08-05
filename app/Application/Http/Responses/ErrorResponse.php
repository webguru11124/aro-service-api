<?php

declare(strict_types=1);

namespace App\Application\Http\Responses;

class ErrorResponse extends AbstractResponse
{
    /**
     * @param string $message
     * @param int $status
     * @param array<string, string> $headers
     * @param int $options
     * @param bool $json
     */
    public function __construct(string $message, int $status = 500, array $headers = [], int $options = 0, bool $json = false)
    {
        parent::__construct($status, $headers, $options, $json);

        $this->setSuccess(false);
        $this->setResult(['message' => $message]);
    }
}
