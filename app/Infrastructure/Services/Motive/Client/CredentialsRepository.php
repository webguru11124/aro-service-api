<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client;

interface CredentialsRepository
{
    public function getApiKey(): string;
}
