<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Responses;

use App\Application\Http\Api\Tracking\V1\Resources\RegionResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\SharedKernel\Entities\Region;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;

class RegionsResponse extends AbstractResponse
{
    /**
     * @param Collection<string, Region> $data
     */
    public function __construct(Collection $data)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);
        $this->setResult(RegionResource::collection($data)->toArray(request()));
    }
}
