<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

class VroomErrorResponseException extends \Exception
{
    public static function requestUnsuccessful(int $statusCode): self
    {
        return new self(__('messages.vroom.request_unsuccessful', ['status_code' => $statusCode]));
    }
}
