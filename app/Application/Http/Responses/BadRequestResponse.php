<?php

declare(strict_types=1);

namespace App\Application\Http\Responses;

use Aptive\Component\Http\HttpStatus;

class BadRequestResponse extends AbstractResponse
{
    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        $status = HttpStatus::BAD_REQUEST;

        parent::__construct($status);

        $this->setSuccess(false);
        $this->setResult(['message' => $message]);
    }
}
