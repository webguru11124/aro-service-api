<?php

declare(strict_types=1);

namespace App\Application\Http\Responses;

use Aptive\Component\Http\HttpStatus;

class UnauthorizedResponse extends AbstractResponse
{
    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct(HttpStatus::UNAUTHORIZED);

        $this->setSuccess(false);
        $this->setResult(['message' => $message]);
    }
}
