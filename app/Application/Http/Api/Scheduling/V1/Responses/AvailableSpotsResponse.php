<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Api\Scheduling\V1\Resources\SpotResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AvailableSpotsResponse extends AbstractResponse
{
    /**
     * @param Collection<Spot> $spots
     * @param Request $request
     */
    public function __construct(
        private readonly Collection $spots,
        private readonly Request $request
    ) {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);

        $result = $this->spots->map(fn (Spot $spot) => SpotResource::make($spot)->toArray($this->request));
        $this->setResult(['spots' => $result]);
    }
}
