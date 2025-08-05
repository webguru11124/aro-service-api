<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Responses;

use App\Application\Http\Api\Tracking\V1\Resources\OfficeResource;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\SharedKernel\Entities\Office;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Support\Collection;

class OfficesResponse extends AbstractResponse
{
    /**
     * @param Collection<string, Office> $data
     */
    public function __construct(Collection $data)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);
        $this->setResult(OfficeResource::collection($data)->toArray(request()));
    }
}
