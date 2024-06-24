<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Resources;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoordinateResource extends JsonResource
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
        /** @var Coordinate $resource */
        $resource = $this->resource;

        return [
            'lat' => $resource->getLatitude(),
            'lng' => $resource->getLongitude(),
        ];
    }
}
