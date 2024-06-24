<?php

declare(strict_types=1);

namespace App\Application\Managers;

use App\Application\DTO\ScoreNotificationsDTO;
use App\Application\Jobs\SendNotificationsJob;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScoreNotificationsManager
{
    public function __construct(
        private readonly GetOfficesByIdsQuery $officesByIdsQuery,
    ) {
    }

    /**
     * @param ScoreNotificationsDTO $dto
     *
     * @throws OfficeNotFoundException
     */
    public function manage(ScoreNotificationsDTO $dto): void
    {
        $offices = $this->officesByIdsQuery->get($dto->officeIds);
        $date = is_null($dto->date) ? Carbon::today() : $dto->date;

        SendNotificationsJob::dispatch($date, $offices);

        Log::info(__('messages.score_notifications.initiated', [
            'office_ids' => $offices->implode(fn (Office $office) => $office->getId(), ', '),
            'date' => $date->toDateString(),
        ]));
    }
}
