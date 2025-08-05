<?php

declare(strict_types=1);

namespace App\Application\Http\Responses;

use Aptive\Component\Http\HttpStatus;

class RequestedResourceNotFoundResponse extends AbstractResponse
{
    public function __construct()
    {
        $status = HttpStatus::NOT_FOUND;

        parent::__construct($status);

        $this->setSuccess(false);
        $this->setResult(['message' => __('messages.not_found.resource')]);
    }
}
