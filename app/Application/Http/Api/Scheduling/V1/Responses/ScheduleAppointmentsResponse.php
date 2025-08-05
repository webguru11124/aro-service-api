<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class ScheduleAppointmentsResponse extends AbstractResponse
{
    public function __construct()
    {
        parent::__construct(HttpStatus::ACCEPTED);

        $this->setSuccess(true);
        $this->setResult(['message' => __('messages.automated_scheduling.process_started')]);
    }
}
