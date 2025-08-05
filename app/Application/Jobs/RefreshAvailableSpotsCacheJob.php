<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Infrastructure\Services\PestRoutes\Actions\RefreshAvailableSpotsCacheAction;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshAvailableSpotsCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param int[] $officeIds
     * @param CarbonInterface|null $startDate
     * @param CarbonInterface|null $endDate
     * @param int|null $ttl
     */
    public function __construct(
        public readonly array $officeIds,
        public readonly CarbonInterface|null $startDate,
        public readonly CarbonInterface|null $endDate,
        public readonly int|null $ttl,
    ) {
        $this->onQueue(config('queue.queues.caching'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        RefreshAvailableSpotsCacheAction $action,
    ): void {
        Log::info(class_basename(self::class) . ' - STARTED', [
            'office_ids' => $this->officeIds,
            'start_date' => $this->startDate?->toDateString(),
            'end_date' => $this->endDate?->toDateString(),
        ]);

        foreach ($this->officeIds as $officeId) {
            try {
                $action->execute($officeId, $this->startDate, $this->endDate, $this->ttl);
            } catch (Throwable $e) {
                Log::error(__('messages.caching.failed_to_refresh_available_spots_cache', [
                    'office_id' => $officeId,
                    'start_date' => $this->startDate?->toDateString(),
                    'end_date' => $this->endDate?->toDateString(),
                ]), [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info(class_basename(self::class) . ' - FINISHED');
    }
}
