<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Exceptions;

class InvalidEventEnd extends \Exception
{
    public static function instance(): self
    {
        return new self('Invalid event end.');
    }
}
