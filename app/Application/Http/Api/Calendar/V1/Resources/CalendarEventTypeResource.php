<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Resources;

use App\Domain\Calendar\Enums\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EventType $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->value,
            'name' => $this->translateName($resource),
        ];
    }

    private function translateName(EventType $eventType): string
    {
        return ucwords(strtolower(implode(' ', explode('_', $eventType->name))));
    }
}
