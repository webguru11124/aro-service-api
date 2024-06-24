<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Caching\Responses;

use Aptive\Component\Http\HttpStatus;
use App\Application\Http\Responses\AbstractResponse;

class RefreshAvailableSpotsCacheResponse extends AbstractResponse
{
    public function __construct()
    {
        parent::__construct(HttpStatus::ACCEPTED);
        $this->setSuccess(true);
        $this->setResult(['message' => __('messages.caching.refresh_available_spots_cache_initiated')]);
    }
}
