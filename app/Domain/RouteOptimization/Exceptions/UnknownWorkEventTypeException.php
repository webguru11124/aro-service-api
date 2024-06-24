<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Exceptions;

class UnknownWorkEventTypeException extends \Exception
{
    /**
     * @param string $workEventType
     *
     * @return UnknownWorkEventTypeException
     */
    public static function instance(string $workEventType): UnknownWorkEventTypeException
    {
        return new self(__('messages.routes_optimization.unknown_work_event_type', [
            'type' => $workEventType,
        ]));
    }
}
