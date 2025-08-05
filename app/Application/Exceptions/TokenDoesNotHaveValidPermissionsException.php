<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

use Exception;

class TokenDoesNotHaveValidPermissionsException extends Exception
{
    /**
     * @param int $userId
     * @param int $groupId
     *
     * @return self
     */
    public static function instance(int $userId, int $groupId): self
    {
        return new self(__('jwt_auth.token_has_invalid_permissions', [
            'user_id' => $userId,
            'group_id' => $groupId,
        ]));
    }
}
