<?php

declare(strict_types=1);

namespace App\Infrastructure\Exceptions;

use Exception;

class OfficeNotFoundException extends Exception
{
    /**
     * @param int[] $nonExistingOfficeIds
     *
     * @return self
     */
    public static function instance(array $nonExistingOfficeIds)
    {
        return new self(__('messages.office.not_found', [
            'ids' => implode(',', $nonExistingOfficeIds),
        ]));
    }
}
