<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\WebsocketTracking;

use App\Domain\Contracts\Services\TrackingService;
use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Exceptions\FailedPublishTrackingDataException;
use Illuminate\Support\Facades\Http;

class WebsocketTrackingService implements TrackingService
{
    private const SEND_TRACKING_DATA_PATH = '/v1/rooms/%s/events/%s';
    private const EVENT_ID_PREFIX = 'track-office-';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private array $config,
        private ProvidedServicesTrackingDataFormatter $formatter
    ) {
    }

    /**
     * Publishes tracking data
     *
     * @param TreatmentState $state
     *
     * @return void
     * @throws FailedPublishTrackingDataException
     */
    public function publish(TreatmentState $state): void
    {
        try {
            Http::post(
                $this->getApiUrl($state->getId()->officeId),
                $this->formatter->format($state)
            )->throw();
        } catch (\Throwable $e) {
            throw new FailedPublishTrackingDataException($e->getMessage());
        }
    }

    private function getApiUrl(int $officeId): string
    {
        return $this->config['api_url'] . sprintf(
            self::SEND_TRACKING_DATA_PATH,
            $this->config['room'],
            self::EVENT_ID_PREFIX . $officeId,
        );
    }
}
