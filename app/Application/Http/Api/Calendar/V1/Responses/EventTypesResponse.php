<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Resources\CalendarEventTypeResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\Calendar\Enums\EventType;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;

class EventTypesResponse extends AbstractResponse
{
    /**
     * @param Collection<EventType> $data
     */
    public function __construct(Collection $data)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);
        $this->setResult(CalendarEventTypeResource::collection($data)->toArray(request()));
    }
}
