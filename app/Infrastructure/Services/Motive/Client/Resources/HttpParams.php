<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

interface HttpParams
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
