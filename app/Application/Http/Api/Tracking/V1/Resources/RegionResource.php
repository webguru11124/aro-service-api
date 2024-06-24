<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Resources;

use App\Domain\SharedKernel\Entities\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
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
        /** @var Region $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->getId(),
            'name' => $resource->getName(),
            'offices' => OfficeResource::collection($resource->getOffices()),
            'boundary' => CoordinateResource::collection(($resource->getBoundaryPolygon()->getVertexes())),
        ];
    }
}
