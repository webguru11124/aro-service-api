<?php

declare(strict_types=1);

namespace App\Application\Http\Api\RouteOptimization\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class SplitOptimizationStateResponse extends AbstractResponse
{
    public function __construct()
    {
        $status = HttpStatus::ACCEPTED;

        parent::__construct($status);

        $this->setSuccess(true);
        $this->setResult(['message' => 'Split optimization state started']);
    }
}
