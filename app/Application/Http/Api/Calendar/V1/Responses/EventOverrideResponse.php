<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class EventOverrideResponse extends AbstractResponse
{
    public function __construct()
    {
        $status = HttpStatus::OK;

        parent::__construct($status);

        $this->setSuccess(true);
        $this->setResult([
            'message' => __('messages.calendar.event_override_added'),
        ]);
    }
}
