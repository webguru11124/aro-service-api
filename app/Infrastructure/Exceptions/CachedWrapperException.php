<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Exception;

class CachedWrapperException extends Exception
{
    public static function wrappedClassImplementationMismatch(): self
    {
        return new self(__('messages.exception.cache_wrapper_interface_mismatch'));
    }
}
