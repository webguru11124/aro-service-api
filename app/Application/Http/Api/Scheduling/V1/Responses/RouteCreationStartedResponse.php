<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class RouteCreationStartedResponse extends AbstractResponse
{
    public function __construct()
    {
        $status = HttpStatus::ACCEPTED;

        parent::__construct($status);

        $this->setSuccess(true);
        $this->setResult(['message' => __('messages.route_creation.started')]);
    }
}
