<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class AppointmentRescheduled extends AbstractResponse
{
    public function __construct(int $id, string|null $executionSid)
    {
        parent::__construct(HttpStatus::OK);

        $this->setSuccess(true);
        $this->setResult([
            'message' => __('messages.appointment.rescheduled'),
            'id' => $id,
            'execution_sid' => $executionSid,
        ]);
    }
}
