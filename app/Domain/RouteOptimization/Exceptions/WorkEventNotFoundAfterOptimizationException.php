<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Exceptions;

use Exception;

class WorkEventNotFoundAfterOptimizationException extends Exception
{
    public static function instance(): self
    {
        return new self(__('messages.exception.work_event_not_found'));
    }
}
