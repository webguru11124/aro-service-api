<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive;

use App\Infrastructure\Services\Motive\Client\CredentialsRepository;

class ConfigCredentialsRepository implements CredentialsRepository
{
    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return config('motive.auth.api_key');
    }
}
