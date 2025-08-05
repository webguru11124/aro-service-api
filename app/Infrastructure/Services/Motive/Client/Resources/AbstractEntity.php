<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

abstract class AbstractEntity
{
    abstract public static function fromApiObject(object $apiObject): self;
}
