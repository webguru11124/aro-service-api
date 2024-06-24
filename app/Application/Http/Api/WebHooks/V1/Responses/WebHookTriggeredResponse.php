<?php

declare(strict_types=1);

namespace App\Application\Http\Api\WebHooks\V1\Responses;

use Aptive\Component\Http\HttpStatus;
use App\Application\Http\Responses\AbstractResponse;

class WebHookTriggeredResponse extends AbstractResponse
{
    public function __construct()
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);
        $this->setResult(['message' => __('messages.webhooks.triggered')]);
    }
}
