<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

class EmptyObject
{
    public static function instance(): self
    {
        return new self();
    }
}
