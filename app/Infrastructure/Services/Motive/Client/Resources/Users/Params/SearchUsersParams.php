<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users\Params;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationAware;
use App\Infrastructure\Services\Motive\Client\Resources\PaginationParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserDutyStatus;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserRole;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserStatus;

class SearchUsersParams extends AbstractHttpParams implements PaginationParams
{
    use PaginationAware;

    public function __construct(
        public readonly UserRole|null $role = null,
        public readonly UserDutyStatus|null $dutyStatus = null,
        public readonly UserStatus|null $status = null,
        public readonly string|null $name = null,
        public readonly int|null $vehicleId = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->withPagination($this->removeNullValuesAndEmptyArraysFromParamsArray([
            'role' => $this->role?->value,
            'duty_status' => $this->dutyStatus?->value,
            'status' => $this->status?->value,
            'name' => $this->name,
        ]));
    }
}
