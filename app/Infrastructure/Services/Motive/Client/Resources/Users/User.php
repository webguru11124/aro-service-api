<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractEntity;

class User extends AbstractEntity
{
    public function __construct(
        public readonly int $id,
        public readonly string|null $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string|null $companyId,
        public readonly UserRole $role,
        public readonly UserStatus $status,
    ) {
    }

    /**
     * @param object $apiObject
     *
     * @return self
     */
    public static function fromApiObject(object $apiObject): self
    {
        return new self(
            id: $apiObject->id,
            email: $apiObject->email ?? null,
            firstName: $apiObject->first_name,
            lastName: $apiObject->last_name,
            companyId: $apiObject->driver_company_id ?? null,
            role: UserRole::from($apiObject->role),
            status: UserStatus::from($apiObject->status)
        );
    }
}
