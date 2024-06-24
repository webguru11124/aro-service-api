<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Resources;

use App\Domain\SharedKernel\Entities\Office;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
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
        /** @var Office $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->getId(),
            'name' => $resource->getName(),
            'region' => $resource->getRegion(),
            'address' => $resource->getAddress(),
            'city' => $resource->getCity(),
            'state' => $resource->getState(),
            'timezone' => $resource->getTimezone(),
            'timezone_name' => $resource->getTimezoneFullName(),
            'location' => CoordinateResource::make($resource->getLocation())->toArray($request),
        ];
    }
}
