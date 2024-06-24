<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class EventCreatedResponse extends AbstractResponse
{
    public function __construct(int $id)
    {
        $status = HttpStatus::CREATED;

        parent::__construct($status);

        $this->setSuccess(true);
        $this->setResult([
            'message' => __('messages.calendar.event_created'),
            'id' => $id,
        ]);
    }
}
