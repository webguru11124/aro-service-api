<?php

declare(strict_types=1);

namespace App\Application\Http\Responses;

use Aptive\Component\Http\HttpStatus;

class ValidationErrorResponse extends ErrorResponse
{
    /**
     * @param string $message
     * @param array<string|int, mixed> $errors
     * @param int $status
     * @param array<string, string> $headers
     * @param int $options
     * @param bool $json
     */
    public function __construct(
        string $message,
        array $errors,
        int $status = HttpStatus::BAD_REQUEST,
        array $headers = [],
        int $options = 0,
        bool $json = false
    ) {
        parent::__construct($message, $status, $headers, $options, $json);

        $this->setResult([
            'message' => $message,
            'errors' => $errors,
        ]);
    }
}
