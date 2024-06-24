<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Resources;

use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpotResource extends JsonResource
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
        /** @var Spot $resource */
        $resource = $this->resource;

        return [
            'spot_id' => $resource->getId(),
            'date' => $resource->getTimeWindow()->getStartAt()->toDateString(),
            'window' => $resource->getWindow(),
            'is_aro_spot' => $resource->isAroSpot(),
        ];
    }
}
