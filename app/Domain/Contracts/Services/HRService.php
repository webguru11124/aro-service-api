<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

interface HRService
{
    /**
     * Gets base64 encoded profile photo by employee id
     *
     * @param string $employeeId
     *
     * @return string
     */
    public function getEmployeePhotoBase64(string $employeeId): string;

    /**
     * Gets base64 encoded profile photos by list of employee ids
     *
     * @param array<string> $employeeIds
     *
     * @return array<string>
     */
    public function getEmployeePhotosBase64(array $employeeIds): array;
}
