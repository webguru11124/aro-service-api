<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarGetParticipantsResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\Calendar\Entities\Employee;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;

class GetParticipantsResponse extends AbstractResponse
{
    /**
     * @param Collection<Employee> $participants
     */
    public function __construct(Collection $participants)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);

        $this->setResult([
            'participants' => !$participants->isEmpty()
                ? CalendarGetParticipantsResource::collection($participants)
                : [],
        ]);
    }
}
