<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\PestRoutesRequestRetry;
use Illuminate\Support\Facades\Log;

class LogPestroutesRequestRetry
{
    private const MESSAGE_TEMPLATE = 'PestRoutes Request retry [%d]. Reason: %d';

    /**
     * Handle the event.
     */
    public function handle(PestRoutesRequestRetry $event): void
    {
        Log::notice(
            sprintf(self::MESSAGE_TEMPLATE, $event->attempt, $event->statusCode),
            [
                'time' => $event->getTime()->toDateTimeString(),
            ]
        );
    }
}
