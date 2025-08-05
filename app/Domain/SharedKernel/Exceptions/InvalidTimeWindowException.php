<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Exceptions;

use Carbon\CarbonInterface;
use Exception;

class InvalidTimeWindowException extends Exception
{
    /**
     * @param CarbonInterface $startAt
     * @param CarbonInterface $endAt
     *
     * @return self
     */
    public static function instance(CarbonInterface $startAt, CarbonInterface $endAt): self
    {
        return new self(__('messages.exception.invalid_time_window', [
            'start_at' => $startAt->toDateTimeString(),
            'end_at' => $endAt->toDateTimeString(),
        ]));
    }
}
