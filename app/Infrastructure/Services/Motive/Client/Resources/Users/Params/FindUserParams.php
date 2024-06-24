<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users\Params;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;

class FindUserParams extends AbstractHttpParams
{
    public function __construct(
        public readonly string|null $email = null,
        public readonly string|null $username = null,
        public readonly string|null $driverCompanyId = null,
        public readonly string|null $phone = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->removeNullValuesAndEmptyArraysFromParamsArray([
            'email' => $this->email,
            'username' => $this->username,
            'driver_company_id' => $this->driverCompanyId,
            'phone' => $this->phone,
        ]);
    }
}
