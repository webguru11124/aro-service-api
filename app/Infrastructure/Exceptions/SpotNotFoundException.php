<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Exception;

class SpotNotFoundException extends Exception
{
    /**
     * @param int $spotId
     *
     * @return self
     */
    public static function instance(int $spotId): self
    {
        return new self(__('messages.not_found.spot', [
            'id' => $spotId,
        ]));
    }
}
