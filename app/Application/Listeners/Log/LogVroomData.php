<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\Vroom\VroomRequestFailed;
use App\Application\Events\Vroom\VroomRequestSent;
use App\Application\Events\Vroom\VroomResponseReceived;
use App\Infrastructure\Helpers\DataMask;
use Illuminate\Support\Facades\Log;

class LogVroomData
{
    private const MESSAGE_REQUEST = 'Vroom Request';
    private const MESSAGE_RESPONSE = 'Vroom Response';
    private const MESSAGE_FAIL = 'Vroom Request Failed';
    private const TIME_FORMAT = 'Y-m-d H:i:s T';
    private const MESSAGE_TEMPLATE = '%s. Date: %s. Office: %d';

    /**
     * Handle the event.
     */
    public function handle(VroomRequestSent|VroomResponseReceived|VroomRequestFailed $event): void
    {
        match (true) {
            $event instanceof VroomRequestSent => $this->logRequest($event),
            $event instanceof VroomResponseReceived => $this->logResponse($event),
            $event instanceof VroomRequestFailed => $this->logFailure($event),
        };
    }

    private function logRequest(VroomRequestSent $event): void
    {
        Log::info(
            sprintf(
                self::MESSAGE_TEMPLATE,
                self::MESSAGE_REQUEST,
                $event->date->toDateString(),
                $event->officeId
            ),
            [
                'time' => $event->getTime()->format(self::TIME_FORMAT),
                'request_id' => $event->requestId,
                'host_url' => $event->url,
                'body' => $event->inputData->toArray(),
            ]
        );
    }

    private function logResponse(VroomResponseReceived $event): void
    {
        $level = $event->response->successful()
            ? 'info'
            : 'error';

        $body = json_decode($event->response->body(), true);

        Log::log(
            $level,
            sprintf(
                self::MESSAGE_TEMPLATE,
                self::MESSAGE_RESPONSE,
                $event->date->toDateString(),
                $event->officeId
            ),
            [
                'time' => $event->getTime()->format(self::TIME_FORMAT),
                'request_id' => $event->requestId,
                'status' => $event->response->status(),
                'body' => DataMask::mask($body),
            ]
        );
    }

    private function logFailure(VroomRequestFailed $event): void
    {
        Log::error(self::MESSAGE_FAIL, [
            'time' => $event->getTime()->format(self::TIME_FORMAT),
            'request_id' => $event->requestId,
        ]);
    }
}
