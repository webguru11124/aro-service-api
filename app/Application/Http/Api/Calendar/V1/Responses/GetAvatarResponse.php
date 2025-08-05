<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Responses\AbstractResponse;
use Aptive\Component\Http\HttpStatus;

class GetAvatarResponse extends AbstractResponse
{
    /**
     * @param string $avatarBase64Data
     */
    public function __construct(string $avatarBase64Data)
    {
        parent::__construct(HttpStatus::OK);
        $this->setSuccess(true);
        $this->setResult([
            'avatarBase64' => $avatarBase64Data,
        ]);
    }
}
