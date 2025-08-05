<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Exception;

class CustomerNotFoundException extends Exception
{
    /**
     * @param int $customerId
     *
     * @return self
     */
    public static function instance(int $customerId): self
    {
        return new self(__('messages.not_found.customer', [
            'id' => $customerId,
        ]));
    }
}
