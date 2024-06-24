<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday;

use App\Domain\Contracts\Services\HRService;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayBadQueryException;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Helpers\WorkdayXmlHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkdayService implements HRService
{
    private const CACHE_KEY_PREFIX = 'employee_photo_';
    private const CACHE_EXPIRY_SECONDS = 60 * 60 * 24;

    public function __construct(
        private WorkdayAPIClient $workdayAPIClient
    ) {
    }

    /**
     * Gets base64 encoded profile photo from Workday by employee id
     *
     * @param string $employeeId
     *
     * @return string
     * @throws WorkdayBadQueryException
     */
    public function getEmployeePhotoBase64(string $employeeId): string
    {
        if (empty($employeeId)) {
            throw new WorkdayBadQueryException(__('messages.workday.invalid_employee_id', ['employee_id' => $employeeId]));
        }

        $cacheKey = $this->buildCacheKey($employeeId);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $photoBase64 = $this->fetchPhotoBase64FromWorkday($employeeId);

            Cache::put($cacheKey, $photoBase64, self::CACHE_EXPIRY_SECONDS);

            return $photoBase64;
        } catch (Throwable $exception) {
            Log::warning(__('messages.workday.failed_to_fetch_photo', ['employee_id' => $employeeId]), [
                'error' => $exception->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Gets base64 encoded profile photos hashtable from Workday with employee ids as keys
     *
     * @param array<string> $employeeIds
     *
     * @return array<string, mixed>
     */
    public function getEmployeePhotosBase64(array $employeeIds): array
    {
        if (empty($employeeIds)) {
            throw new WorkdayBadQueryException(__('messages.workday.empty_employee_ids'));
        }

        sort($employeeIds);
        $cacheKey = $this->buildCacheKey(md5(implode(',', $employeeIds)));

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $photosBase64 = $this->fetchPhotosBase64FromWorkday($employeeIds);

            Cache::put($cacheKey, json_encode($photosBase64), self::CACHE_EXPIRY_SECONDS);

            return $photosBase64;
        } catch (Throwable $exception) {
            $ids = implode(', ', $employeeIds);
            Log::warning(__('messages.workday.failed_to_fetch_photos'), [
                'error' => $exception->getMessage(),
                'employee_ids' => $ids,
            ]);

            return [];
        }
    }

    /**
     * @param string $workdayId
     *
     * @return string
     * @throws WorkdayErrorException
     */
    private function fetchPhotoBase64FromWorkday(string $workdayId): string
    {
        $xmlResponse = $this->workdayAPIClient->post(
            config('workday.services.human_resources_url'),
            ['body' => WorkdayXmlHelper::getWorkerPhotoXMLRequest($workdayId)],
        );

        return WorkdayXmlHelper::getWorkerPhotoBase64FromXmlResponse($xmlResponse);
    }

    /**
     * @param array<string> $workdayIds
     *
     * @return array<string, mixed>
     * @throws WorkdayErrorException
     */
    private function fetchPhotosBase64FromWorkday(array $workdayIds): array
    {
        $xmlResponse = $this->workdayAPIClient->post(
            config('workday.services.human_resources_url'),
            ['body' => WorkdayXmlHelper::getWorkerPhotosXMLRequest($workdayIds)],
        );

        return WorkdayXmlHelper::getWorkerPhotosBase64FromXmlResponse($xmlResponse);
    }

    private function buildCacheKey(string $workdayId): string
    {
        return self::CACHE_KEY_PREFIX . $workdayId;
    }
}
