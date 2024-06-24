<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Resources;

use App\Domain\Calendar\Entities\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarGetParticipantsResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Participant $participant */
        $participant = $this->resource;

        return [
            'id' => $participant->getId(),
            'name' => $participant->getName(),
            'is_invited' => $participant->isInvited(),
            'external_id' => $participant->getWorkdayId(),
        ];
    }
}
