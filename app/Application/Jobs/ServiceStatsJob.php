<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Domain\Contracts\Repositories\TreatmentStateRepository;
use App\Domain\Contracts\Services\TrackingService;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Exceptions\FailedPublishTrackingDataException;
use App\Domain\Tracking\Factories\TreatmentStateFactory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ServiceStatsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 min

    public function __construct(
        public readonly Carbon $date,
        public readonly Office $office,
    ) {
        $this->onQueue(config('queue.queues.service-stats'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        TrackingService $trackingService,
        TreatmentStateFactory $treatmentStateFactory,
        TreatmentStateRepository $treatmentStateRepository,
    ): void {
        $state = $treatmentStateFactory->create($this->office, $this->date);

        try {
            $trackingService->publish($state);
            $treatmentStateRepository->save($state);
        } catch (FailedPublishTrackingDataException $e) {
            Log::notice(__('messages.service_stats.failed_to_publish_tracking_data', [
                'office' => $this->office->getName(),
                'office_id' => $this->office->getId(),
                'date' => $this->date->toDateString(),
            ]), [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
