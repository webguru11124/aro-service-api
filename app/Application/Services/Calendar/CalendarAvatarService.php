<?php

declare(strict_types=1);

namespace App\Application\Services\Calendar;

use App\Domain\Contracts\Services\HRService;
use Illuminate\Support\Facades\Cache;
use Imagick;

class CalendarAvatarService
{
    private const CACHE_KEY_PREFIX = 'employee_resized_photo_';
    private const CACHE_EXPIRY_SECONDS = 60 * 60 * 24;
    private const BASE_SIZE = 256;

    public function __construct(
        private HRService $workdayService,
    ) {
    }

    /**
     * Gets base64 encoded profile photo from Workday by employee id and resizes it
     *
     * @param string $externalId
     *
     * @return string
     */
    public function getAvatar(string $externalId): string
    {
        $cacheKey = $this->buildCacheKey($externalId);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $avatarString = $this->workdayService->getEmployeePhotoBase64($externalId);

        if (empty($avatarString)) {
            return '';
        }

        $resizedAvatar = $this->resizeAvatar($avatarString);

        Cache::put($cacheKey, $resizedAvatar, self::CACHE_EXPIRY_SECONDS);

        return $resizedAvatar;
    }

    private function resizeAvatar(string $avatarString): string
    {
        $imageData = base64_decode($avatarString);

        $image = new Imagick();
        $image->readImageBlob($imageData);

        $image->resizeImage(self::BASE_SIZE, self::BASE_SIZE, Imagick::FILTER_LANCZOS, 1);

        return base64_encode($image->getImageBlob());
    }

    private function buildCacheKey(string $externalId): string
    {
        return self::CACHE_KEY_PREFIX . $externalId;
    }
}
